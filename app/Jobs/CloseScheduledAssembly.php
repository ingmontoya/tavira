<?php

namespace App\Jobs;

use App\Events\AssemblyClosed;
use App\Models\Assembly;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CloseScheduledAssembly implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Assembly $assembly
    ) {}

    public function handle(): void
    {
        try {
            // Only close if assembly is still in progress
            if ($this->assembly->status !== 'in_progress') {
                Log::info('Assembly is no longer in progress, skipping scheduled closure', [
                    'assembly_id' => $this->assembly->id,
                    'current_status' => $this->assembly->status,
                ]);
                return;
            }

            // Close all active votes first
            $activeVotes = $this->assembly->votes()->where('status', 'active')->get();
            
            foreach ($activeVotes as $vote) {
                $vote->close();
                Log::info('Vote closed during assembly closure', [
                    'vote_id' => $vote->id,
                    'title' => $vote->title,
                    'participation' => $vote->participation_stats,
                ]);
            }

            // Close the assembly
            $this->assembly->close();
            
            AssemblyClosed::dispatch($this->assembly);

            Log::info('Assembly closed automatically', [
                'assembly_id' => $this->assembly->id,
                'title' => $this->assembly->title,
                'duration_minutes' => $this->assembly->duration_minutes,
                'votes_closed' => $activeVotes->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to close scheduled assembly', [
                'assembly_id' => $this->assembly->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
