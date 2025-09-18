<?php

namespace App\Jobs;

use App\Events\AssemblyClosed;
use App\Models\Vote;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CloseExpiredVotes implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $expiredVotes = Vote::where('status', 'active')
            ->where('closes_at', '<=', now())
            ->get();

        Log::info('Closing expired votes', ['count' => $expiredVotes->count()]);

        foreach ($expiredVotes as $vote) {
            try {
                $vote->close();

                Log::info('Vote closed automatically', [
                    'vote_id' => $vote->id,
                    'assembly_id' => $vote->assembly_id,
                    'title' => $vote->title,
                    'participation' => $vote->participation_stats,
                ]);

                // Check if all votes in assembly are closed
                if ($this->shouldCloseAssembly($vote)) {
                    $assembly = $vote->assembly;
                    $assembly->close();

                    AssemblyClosed::dispatch($assembly);

                    Log::info('Assembly closed automatically after all votes completed', [
                        'assembly_id' => $assembly->id,
                        'title' => $assembly->title,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Failed to close expired vote', [
                    'vote_id' => $vote->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function shouldCloseAssembly(Vote $vote): bool
    {
        $assembly = $vote->assembly;

        // Don't auto-close if assembly is not in progress
        if ($assembly->status !== 'in_progress') {
            return false;
        }

        // Check if all votes in the assembly are closed
        $openVotes = $assembly->votes()->where('status', 'active')->count();

        return $openVotes === 0;
    }
}
