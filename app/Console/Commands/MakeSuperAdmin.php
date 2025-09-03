<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class MakeSuperAdmin extends Command
{
    protected $signature = 'user:make-superadmin {email}';
    protected $description = 'Make a user a superadmin';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }
        
        // Ensure superadmin role exists
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin']);
        
        if ($user->hasRole('superadmin')) {
            $this->info("User {$user->name} ({$email}) is already a superadmin.");
            return 0;
        }
        
        $user->assignRole('superadmin');
        
        $this->info("User {$user->name} ({$email}) has been made a superadmin successfully.");
        return 0;
    }
}