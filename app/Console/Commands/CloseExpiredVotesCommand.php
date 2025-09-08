<?php

namespace App\Console\Commands;

use App\Jobs\CloseExpiredVotes;
use Illuminate\Console\Command;

class CloseExpiredVotesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voting:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close expired votes across all tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching job to close expired votes...');
        
        CloseExpiredVotes::dispatch();
        
        $this->info('Job dispatched successfully.');
        
        return 0;
    }
}
