<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_displays_residents_list()
    {
        $residents = Resident::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('residents.index'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->component('residents/Index')
                ->has('residents.data', 3);
        });
    }

    public function test_index_can_search_residents()
    {
        $resident1 = Resident::factory()->create(['first_name' => 'Juan']);
        $resident2 = Resident::factory()->create(['first_name' => 'Pedro']);

        $response = $this->actingAs($this->user)->get(route('residents.index', ['search' => 'Juan']));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->has('residents.data', 1)
                ->where('residents.data.0.first_name', 'Juan');
        });
    }

    public function test_index_can_filter_by_status()
    {
        Resident::factory()->create(['status' => 'Active']);
        Resident::factory()->create(['status' => 'Inactive']);

        $response = $this->actingAs($this->user)->get(route('residents.index', ['status' => 'Active']));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->has('residents.data', 1)
                ->where('residents.data.0.status', 'Active');
        });
    }

    public function test_create_displays_create_form()
    {
        $response = $this->actingAs($this->user)->get(route('residents.create'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->component('residents/Create');
        });
    }

    public function test_store_creates_new_resident()
    {
        $residentData = [
            'document_type' => 'CC',
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan.perez@example.com',
            'phone' => '555-1234',
            'mobile_phone' => '555-123-4567',
            'birth_date' => '1990-01-01',
            'gender' => 'M',
            'emergency_contact' => 'María Pérez - 555-9876',
            'apartment_number' => '101',
            'tower' => 'A',
            'resident_type' => 'Owner',
            'status' => 'Active',
            'start_date' => '2024-01-01',
            'notes' => 'Propietario original',
        ];

        $response = $this->actingAs($this->user)->post(route('residents.store'), $residentData);

        $response->assertRedirect(route('residents.index'));
        $this->assertDatabaseHas('residents', [
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan.perez@example.com',
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post(route('residents.store'), []);

        $response->assertSessionHasErrors([
            'document_type',
            'document_number',
            'first_name',
            'last_name',
            'email',
            'apartment_number',
            'resident_type',
            'status',
            'start_date',
        ]);
    }

    public function test_store_validates_unique_document_number()
    {
        $existingResident = Resident::factory()->create(['document_number' => '12345678']);

        $response = $this->actingAs($this->user)->post(route('residents.store'), [
            'document_type' => 'CC',
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan.perez@example.com',
            'apartment_number' => '101',
            'resident_type' => 'Owner',
            'status' => 'Active',
            'start_date' => '2024-01-01',
        ]);

        $response->assertSessionHasErrors(['document_number']);
    }

    public function test_store_validates_unique_email()
    {
        $existingResident = Resident::factory()->create(['email' => 'test@example.com']);

        $response = $this->actingAs($this->user)->post(route('residents.store'), [
            'document_type' => 'CC',
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'test@example.com',
            'apartment_number' => '101',
            'resident_type' => 'Owner',
            'status' => 'Active',
            'start_date' => '2024-01-01',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_show_displays_resident_details()
    {
        $resident = Resident::factory()->create();

        $response = $this->actingAs($this->user)->get(route('residents.show', $resident));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($resident) {
            $page->component('residents/Show')
                ->where('resident.id', $resident->id);
        });
    }

    public function test_edit_displays_edit_form()
    {
        $resident = Resident::factory()->create();

        $response = $this->actingAs($this->user)->get(route('residents.edit', $resident));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) use ($resident) {
            $page->component('residents/Edit')
                ->where('resident.id', $resident->id);
        });
    }

    public function test_update_modifies_resident()
    {
        $resident = Resident::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $updateData = [
            'document_type' => $resident->document_type,
            'document_number' => $resident->document_number,
            'first_name' => 'Carlos',
            'last_name' => 'González',
            'email' => $resident->email,
            'apartment_number' => $resident->apartment_number,
            'resident_type' => $resident->resident_type,
            'status' => $resident->status,
            'start_date' => $resident->start_date,
        ];

        $response = $this->actingAs($this->user)->put(route('residents.update', $resident), $updateData);

        $response->assertRedirect(route('residents.index'));
        $this->assertDatabaseHas('residents', [
            'id' => $resident->id,
            'first_name' => 'Carlos',
            'last_name' => 'González',
        ]);
    }

    public function test_update_validates_unique_document_number_except_current()
    {
        $resident1 = Resident::factory()->create(['document_number' => '12345678']);
        $resident2 = Resident::factory()->create(['document_number' => '87654321']);

        $updateData = [
            'document_type' => $resident2->document_type,
            'document_number' => '12345678',
            'first_name' => $resident2->first_name,
            'last_name' => $resident2->last_name,
            'email' => $resident2->email,
            'apartment_number' => $resident2->apartment_number,
            'resident_type' => $resident2->resident_type,
            'status' => $resident2->status,
            'start_date' => $resident2->start_date,
        ];

        $response = $this->actingAs($this->user)->put(route('residents.update', $resident2), $updateData);

        $response->assertSessionHasErrors(['document_number']);
    }

    public function test_destroy_deletes_resident()
    {
        $resident = Resident::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('residents.destroy', $resident));

        $response->assertRedirect(route('residents.index'));
        $this->assertDatabaseMissing('residents', ['id' => $resident->id]);
    }

    public function test_guest_cannot_access_residents_routes()
    {
        $resident = Resident::factory()->create();

        $this->get(route('residents.index'))->assertRedirect('/login');
        $this->get(route('residents.create'))->assertRedirect('/login');
        $this->post(route('residents.store'), [])->assertRedirect('/login');
        $this->get(route('residents.show', $resident))->assertRedirect('/login');
        $this->get(route('residents.edit', $resident))->assertRedirect('/login');
        $this->put(route('residents.update', $resident), [])->assertRedirect('/login');
        $this->delete(route('residents.destroy', $resident))->assertRedirect('/login');
    }
}
