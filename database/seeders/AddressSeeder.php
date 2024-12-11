<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::all()->each(function ($user) {
            foreach (range(1, rand(1, 3)) as $index) {
                Address::create([
                    'user_id' => $user->id,
                    'address_line1' => fake()->streetAddress(),
                    'address_line2' => fake()->optional()->secondaryAddress(),
                    'pincode' => fake()->numberBetween(1000, 999999),
                    'city' => fake()->city(),
                    'state' => fake()->state(),
                    'type' => fake()->randomElement(['home', 'office']),
                ]);
            }
        });
    }
}
