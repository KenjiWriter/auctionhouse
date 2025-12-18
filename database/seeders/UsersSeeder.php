<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'phone' => '1234567890',
                'address' => 'One Admin Way',
                'city' => 'Admin City',
                'postal_code' => '99999',
                'country' => 'Adminland',
                'marketing_opt_in' => false,
                'terms_accepted' => true,
            ]
        );

        // Demo Users
        for ($i = 1; $i <= 20; $i++) {
            User::firstOrCreate(
                ['email' => "demo{$i}@demo.com"],
                [
                    'name' => "Demo User {$i}",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ] + User::factory()->make()->makeHidden(['avatar_url', 'email'])->toArray()
            );
        }
    }
}
