<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_can_be_created()
    {
        $resident = Resident::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'document_type' => 'CC',
            'document_number' => '12345678',
            'email' => 'juan.perez@example.com',
            'apartment_number' => '101',
            'tower' => 'A',
            'resident_type' => 'Owner',
            'status' => 'Active',
            'start_date' => '2024-01-01',
        ]);

        $this->assertDatabaseHas('residents', [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'document_number' => '12345678',
            'email' => 'juan.perez@example.com',
        ]);
    }

    public function test_resident_full_name_attribute()
    {
        $resident = Resident::factory()->make([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        $this->assertEquals('Juan Pérez', $resident->full_name);
    }

    public function test_resident_apartment_full_attribute()
    {
        $resident = Resident::factory()->make([
            'apartment_number' => '101',
            'tower' => 'A',
        ]);

        $this->assertEquals('A-101', $resident->apartment_full);
    }

    public function test_resident_apartment_full_attribute_without_tower()
    {
        $resident = Resident::factory()->make([
            'apartment_number' => '101',
            'tower' => null,
        ]);

        $this->assertEquals('101', $resident->apartment_full);
    }

    public function test_active_scope()
    {
        Resident::factory()->create(['status' => 'Active']);
        Resident::factory()->create(['status' => 'Inactive']);

        $activeResidents = Resident::active()->get();

        $this->assertCount(1, $activeResidents);
        $this->assertEquals('Active', $activeResidents->first()->status);
    }

    public function test_inactive_scope()
    {
        Resident::factory()->create(['status' => 'Active']);
        Resident::factory()->create(['status' => 'Inactive']);

        $inactiveResidents = Resident::inactive()->get();

        $this->assertCount(1, $inactiveResidents);
        $this->assertEquals('Inactive', $inactiveResidents->first()->status);
    }

    public function test_owners_scope()
    {
        Resident::factory()->create(['resident_type' => 'Owner']);
        Resident::factory()->create(['resident_type' => 'Tenant']);

        $owners = Resident::owners()->get();

        $this->assertCount(1, $owners);
        $this->assertEquals('Owner', $owners->first()->resident_type);
    }

    public function test_tenants_scope()
    {
        Resident::factory()->create(['resident_type' => 'Owner']);
        Resident::factory()->create(['resident_type' => 'Tenant']);

        $tenants = Resident::tenants()->get();

        $this->assertCount(1, $tenants);
        $this->assertEquals('Tenant', $tenants->first()->resident_type);
    }

    public function test_by_apartment_scope()
    {
        Resident::factory()->create(['apartment_number' => '101', 'tower' => 'A']);
        Resident::factory()->create(['apartment_number' => '102', 'tower' => 'A']);

        $residents = Resident::byApartment('101', 'A')->get();

        $this->assertCount(1, $residents);
        $this->assertEquals('101', $residents->first()->apartment_number);
    }

    public function test_by_tower_scope()
    {
        Resident::factory()->create(['tower' => 'A']);
        Resident::factory()->create(['tower' => 'B']);

        $residents = Resident::byTower('A')->get();

        $this->assertCount(1, $residents);
        $this->assertEquals('A', $residents->first()->tower);
    }

    public function test_document_number_must_be_unique()
    {
        Resident::factory()->create(['document_number' => '12345678']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Resident::factory()->create(['document_number' => '12345678']);
    }

    public function test_email_must_be_unique()
    {
        Resident::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Resident::factory()->create(['email' => 'test@example.com']);
    }

    public function test_resident_dates_are_cast_to_carbon()
    {
        $resident = Resident::factory()->create([
            'birth_date' => '1990-01-01',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $resident->birth_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $resident->start_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $resident->end_date);
    }

    public function test_documents_are_cast_to_array()
    {
        $documents = ['id_copy.pdf', 'contract.pdf'];
        $resident = Resident::factory()->create([
            'documents' => $documents,
        ]);

        $this->assertIsArray($resident->documents);
        $this->assertEquals($documents, $resident->documents);
    }
}