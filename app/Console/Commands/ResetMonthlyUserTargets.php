<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetMonthlyUserTargets extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'targets:reset-monthly-user-targets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all user_targets and user_monthly_targets records at the start of each month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to reset monthly user targets...');

        $deletedUserTargets = DB::table('user_targets')->delete();
        $this->info("Deleted {$deletedUserTargets} records from user_targets.");

        $deletedUserMonthlyTargets = DB::table('user_monthly_targets')->delete();
        $this->info("Deleted {$deletedUserMonthlyTargets} records from user_monthly_targets.");

        $this->info('Successfully reset all user targets for the new month!');
    }
}
