<?php

namespace Tests\Feature;

use App\Events\PanicAlertTriggered;
use App\Models\Apartment;
use App\Models\PanicAlert;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PanicAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize tenancy for testing
        $this->initializeTenancy();

        // Enable panic button feature for tenant
        TenantFeature::create([
            'tenant_id' => tenant('id'),
            'feature' => 'panic_button',
            'enabled' => true,
        ]);
    }

    /** @test */
    public function user_can_trigger_panic_alert()
    {
        Event::fake();

        $user = User::factory()->create();
        $apartment = Apartment::factory()->create();

        // Associate user with apartment (assuming resident relationship exists)
        $user->update(['apartment_id' => $apartment->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/panic-alerts', [
                'lat' => 4.7110,
                'lng' => -74.0721,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'alert' => [
                    'id',
                    'status',
                    'created_at',
                    'countdown_expires_at'
                ]
            ]);

        $this->assertDatabaseHas('panic_alerts', [
            'user_id' => $user->id,
            'apartment_id' => $apartment->id,
            'status' => 'triggered',
            'lat' => 4.7110,
            'lng' => -74.0721,
        ]);

        Event::assertDispatched(PanicAlertTriggered::class);
    }

    /** @test */
    public function panic_alert_can_be_cancelled_within_time_window()
    {
        $user = User::factory()->create();
        $panicAlert = PanicAlert::factory()->create([
            'user_id' => $user->id,
            'status' => 'triggered',
            'created_at' => now(), // Just created
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/panic-alerts/{$panicAlert->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Alerta de pánico cancelada',
            ]);

        $this->assertDatabaseHas('panic_alerts', [
            'id' => $panicAlert->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function panic_alert_cannot_be_cancelled_after_time_window()
    {
        $user = User::factory()->create();
        $panicAlert = PanicAlert::factory()->create([
            'user_id' => $user->id,
            'status' => 'triggered',
            'created_at' => now()->subSeconds(15), // 15 seconds ago
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/panic-alerts/{$panicAlert->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'El tiempo para cancelar la alerta ha expirado',
            ]);

        $this->assertDatabaseHas('panic_alerts', [
            'id' => $panicAlert->id,
            'status' => 'triggered', // Should remain triggered
        ]);
    }

    /** @test */
    public function only_alert_owner_can_cancel_alert()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $panicAlert = PanicAlert::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'triggered',
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/panic-alerts/{$panicAlert->id}/cancel");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'No autorizado',
            ]);
    }

    /** @test */
    public function admin_can_resolve_panic_alert()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin_conjunto');

        $panicAlert = PanicAlert::factory()->create([
            'status' => 'triggered',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/panic-alerts/{$panicAlert->id}/resolve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Alerta de pánico marcada como resuelta',
            ]);

        $this->assertDatabaseHas('panic_alerts', [
            'id' => $panicAlert->id,
            'status' => 'resolved',
        ]);
    }

    /** @test */
    public function regular_user_cannot_resolve_panic_alert()
    {
        $user = User::factory()->create();
        $panicAlert = PanicAlert::factory()->create([
            'status' => 'triggered',
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/panic-alerts/{$panicAlert->id}/resolve");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'No tienes permisos para resolver alertas',
            ]);
    }

    /** @test */
    public function admin_can_view_active_alerts()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin_conjunto');

        $user = User::factory()->create(['name' => 'Juan Pérez']);
        $apartment = Apartment::factory()->create(['number' => '101']);

        // Create active alerts
        PanicAlert::factory()->create([
            'user_id' => $user->id,
            'apartment_id' => $apartment->id,
            'status' => 'triggered',
        ]);

        PanicAlert::factory()->create([
            'user_id' => $user->id,
            'apartment_id' => $apartment->id,
            'status' => 'confirmed',
        ]);

        // Create resolved alert (should not appear)
        PanicAlert::factory()->create([
            'status' => 'resolved',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/panic-alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'alerts' => [
                    '*' => [
                        'id',
                        'user_name',
                        'apartment',
                        'status',
                        'created_at',
                        'is_active'
                    ]
                ]
            ]);

        $this->assertCount(2, $response->json('alerts'));
    }

    /** @test */
    public function user_can_view_their_panic_alert_history()
    {
        $user = User::factory()->create();

        // Create alerts for this user
        PanicAlert::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        // Create alerts for other user (should not appear)
        PanicAlert::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/panic-alerts/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'alerts' => [
                    'data' => [
                        '*' => [
                            'id',
                            'status',
                            'created_at',
                        ]
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('alerts.data'));
    }

    /** @test */
    public function panic_button_requires_feature_flag()
    {
        // Disable panic button feature
        TenantFeature::where('tenant_id', tenant('id'))
            ->where('feature', 'panic_button')
            ->update(['enabled' => false]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/panic-alerts', [
                'lat' => 4.7110,
                'lng' => -74.0721,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Esta funcionalidad no está disponible en su plan.',
                'feature' => 'panic_button',
                'enabled' => false,
            ]);
    }

    /** @test */
    public function panic_alert_validates_coordinates()
    {
        $user = User::factory()->create();

        // Test invalid latitude
        $response = $this->actingAs($user)
            ->postJson('/api/panic-alerts', [
                'lat' => 95, // Invalid latitude
                'lng' => -74.0721,
            ]);

        $response->assertStatus(422);

        // Test invalid longitude
        $response = $this->actingAs($user)
            ->postJson('/api/panic-alerts', [
                'lat' => 4.7110,
                'lng' => 200, // Invalid longitude
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function panic_alert_works_without_coordinates()
    {
        Event::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/panic-alerts', []);

        $response->assertStatus(201);

        $this->assertDatabaseHas('panic_alerts', [
            'user_id' => $user->id,
            'status' => 'triggered',
            'lat' => null,
            'lng' => null,
        ]);

        Event::assertDispatched(PanicAlertTriggered::class);
    }
}