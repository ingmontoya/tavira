<?php

use App\Models\Tenant;
use App\Models\TenantFeature;

test('feature api returns enabled feature', function () {
    $tenant = Tenant::factory()->create();
    TenantFeature::enableFeature($tenant->id, 'correspondence');

    $response = $this->get("/api/internal/features/{$tenant->id}/correspondence");

    $response->assertStatus(200)
        ->assertJson([
            'feature' => 'correspondence',
            'enabled' => true,
            'tenant_id' => $tenant->id,
        ]);
});

test('feature api returns disabled feature', function () {
    $tenant = Tenant::factory()->create();
    TenantFeature::disableFeature($tenant->id, 'correspondence');

    $response = $this->get("/api/internal/features/{$tenant->id}/correspondence");

    $response->assertStatus(200)
        ->assertJson([
            'feature' => 'correspondence',
            'enabled' => false,
            'tenant_id' => $tenant->id,
        ]);
});

test('feature api returns false for non-existent feature', function () {
    $tenant = Tenant::factory()->create();

    $response = $this->get("/api/internal/features/{$tenant->id}/non-existent");

    $response->assertStatus(200)
        ->assertJson([
            'feature' => 'non-existent',
            'enabled' => false,
            'tenant_id' => $tenant->id,
        ]);
});

test('features api returns all features for tenant', function () {
    $tenant = Tenant::factory()->create();
    TenantFeature::enableFeature($tenant->id, 'correspondence');
    TenantFeature::enableFeature($tenant->id, 'accounting');
    TenantFeature::disableFeature($tenant->id, 'maintenance_requests');

    $response = $this->get("/api/internal/features/{$tenant->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'tenant_id',
            'features',
        ]);

    $data = $response->json();

    expect($data['tenant_id'])->toBe($tenant->id);
    expect($data['features']['correspondence'])->toBe(true);
    expect($data['features']['accounting'])->toBe(true);
    expect($data['features']['maintenance_requests'])->toBe(false);
});
