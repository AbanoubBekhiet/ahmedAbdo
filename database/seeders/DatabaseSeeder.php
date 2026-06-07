<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'admin',
            'password' => Hash::make('password'),
            'phone_number' => '01283324043',
            'role' => 'admin'
        ]);
        User::create([
            'name' => 'customer',
            'password' => Hash::make('password'),
            'phone_number' => '01555126141',
            'role' => 'customer'
        ]);

        User::create([
            'name' => 'delivery',
            'password' => Hash::make('password'),
            'phone_number' => '01283324042',
            'role' => 'delivery'
        ]);

        Setting::create([
            "min_order_products_count" => 1,
            "min_order_total_price" => 1000,
            "phone_number" => "01000000000",
        ]);
    }
}
