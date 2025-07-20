<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Academic Management
            'view_programs',
            'create_programs',
            'edit_programs',
            'delete_programs',
            'view_study_plans',
            'create_study_plans',
            'edit_study_plans',
            'delete_study_plans',
            'view_periods',
            'create_periods',
            'edit_periods',
            'delete_periods',
            'view_subjects',
            'create_subjects',
            'edit_subjects',
            'delete_subjects',
            'view_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',

            // Enrollment Management
            'view_enrollments',
            'create_enrollments',
            'edit_enrollments',
            'delete_enrollments',
            'view_academic_history',

            // Student Management
            'view_students',
            'create_students',
            'edit_students',
            'delete_students',

            // User Management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $academic_director = Role::firstOrCreate(['name' => 'academic_director']);
        $teacher = Role::firstOrCreate(['name' => 'teacher']);
        $student = Role::firstOrCreate(['name' => 'student']);
        $parent = Role::firstOrCreate(['name' => 'parent']);
        $treasury = Role::firstOrCreate(['name' => 'treasury']);

        // Assign permissions to roles
        $superadmin->givePermissionTo(Permission::all());

        $academic_director->givePermissionTo([
            'view_programs', 'create_programs', 'edit_programs',
            'view_study_plans', 'create_study_plans', 'edit_study_plans',
            'view_periods', 'create_periods', 'edit_periods',
            'view_subjects', 'create_subjects', 'edit_subjects',
            'view_groups', 'create_groups', 'edit_groups',
            'view_enrollments', 'create_enrollments', 'edit_enrollments',
            'view_students', 'create_students', 'edit_students',
            'view_academic_history',
        ]);

        $teacher->givePermissionTo([
            'view_groups',
            'view_enrollments',
            'view_students',
            'view_academic_history',
        ]);

        $student->givePermissionTo([
            'view_academic_history',
        ]);
    }
}
