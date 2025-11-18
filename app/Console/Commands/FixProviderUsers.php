<?php

namespace App\Console\Commands;

use App\Models\Central\Provider;
use App\Models\User;
use Illuminate\Console\Command;

class FixProviderUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'providers:fix-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix provider users that are missing provider_id association';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Searching for provider users missing provider_id...');

        // Find users with provider role but no provider_id
        $providerUsers = User::role('provider')
            ->whereNull('provider_id')
            ->get();

        if ($providerUsers->isEmpty()) {
            $this->info('No provider users found without provider_id.');

            return 0;
        }

        $this->info("Found {$providerUsers->count()} provider user(s) without provider_id.");

        foreach ($providerUsers as $user) {
            // Try to find provider by email
            $provider = Provider::where('email', $user->email)
                ->orWhere('contact_email', $user->email)
                ->first();

            if ($provider) {
                $user->update(['provider_id' => $provider->id]);
                $this->info("✓ Updated user {$user->email} with provider_id: {$provider->id} ({$provider->name})");
            } else {
                $this->warn("✗ No provider found for user {$user->email}");
            }
        }

        $this->info('Done!');

        return 0;
    }
}
