<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetMonthlyOrdersPrice extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'profiles:reset-monthly-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the total_orders_price_in_current_month to zero for all profiles at the start of the month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to reset monthly order prices...');

        DB::table('profiles')->update([
            'total_orders_price_in_current_month' => 0
        ]);

        $this->info('Successfully reset all monthly order prices to zero!');
    }
}
