<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '555-0199',
            'address' => '123 Test Lane',
            'city' => 'Testville',
            'postal_code' => '90001',
            'country' => 'Testland',
            'marketing_opt_in' => true,
            'terms_accepted' => true,
        ]);

        $this->call([
            UsersSeeder::class,
            CategoriesSeeder::class,
        ]);
    }
}
