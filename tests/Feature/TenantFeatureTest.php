<?php

use App\Models\Tenant;
use App\Models\TenantFeature;

test('can enable feature for tenant', function () {
    $tenant = Tenant::factory()->create();
    
    TenantFeature::enableFeature($tenant->id, 'correspondence');
    
    expect(TenantFeature::isFeatureEnabled($tenant->id, 'correspondence'))->toBeTrue();
    
    $feature = TenantFeature::where('tenant_id', $tenant->id)
        ->where('feature', 'correspondence')
        ->first();
    
    expect($feature)->not->toBeNull();
    expect($feature->enabled)->toBeTrue();
});

test('can disable feature for tenant', function () {
    $tenant = Tenant::factory()->create();
    
    // Enable first
    TenantFeature::enableFeature($tenant->id, 'correspondence');
    expect(TenantFeature::isFeatureEnabled($tenant->id, 'correspondence'))->toBeTrue();
    
    // Then disable
    TenantFeature::disableFeature($tenant->id, 'correspondence');
    expect(TenantFeature::isFeatureEnabled($tenant->id, 'correspondence'))->toBeFalse();
});

test('returns false for non-existent feature', function () {
    $tenant = Tenant::factory()->create();
    
    expect(TenantFeature::isFeatureEnabled($tenant->id, 'non-existent-feature'))->toBeFalse();
});

test('can update existing feature status', function () {
    $tenant = Tenant::factory()->create();
    
    // Enable
    TenantFeature::enableFeature($tenant->id, 'correspondence');
    expect(TenantFeature::isFeatureEnabled($tenant->id, 'correspondence'))->toBeTrue();
    
    // Disable
    TenantFeature::disableFeature($tenant->id, 'correspondence');
    expect(TenantFeature::isFeatureEnabled($tenant->id, 'correspondence'))->toBeFalse();
    
    // Should only have one record
    $count = TenantFeature::where('tenant_id', $tenant->id)
        ->where('feature', 'correspondence')
        ->count();
    
    expect($count)->toBe(1);
});