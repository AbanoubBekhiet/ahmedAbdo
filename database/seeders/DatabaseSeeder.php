<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use App\Models\Profile;
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
        Profile::create([
            'user_id' => 1,
            'shop_name' => 'Tech Zone Shop',
            'latitude'=> 30.0444,
            'longitude'=> 31.2357,
            'address' => '12 El-Tahrir Square, Cairo, Egypt',
            'fcm_token' => 'e_dsOA57QUO3K8m-pVymMY:APA91bG38YToHSaYt2ySryOHGKBKdUI4mtE7EQZI9oV_EEeEQ3ky9gGioB3dImhPxYHfQiDeOgKMG_GgW3UxC3cAoONiJDkBytLlp-s8YJGsEv05LR_crvc',
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
