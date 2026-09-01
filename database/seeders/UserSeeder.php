<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'id' => Str::uuid(),
            'username' => 'admin',
            'full_name' => 'Administrator',
            'email' => 'admin@medicare.com',
            // store raw password; model will hash via 'password' cast
            'password' => 'password',
            'role' => 'admin',
        ]);

        // Create customer user
        User::create([
            'id' => Str::uuid(),
            'username' => 'customer',
            'full_name' => 'John Doe',
            'email' => 'customer@example.com',
            'password' => 'password',
            'role' => 'customer',
            'address' => '123 Main St',
            'city' => 'New York',
            'contact' => '555-123-4567',
        ]);

        // Create additional customers
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'id' => Str::uuid(),
                'username' => 'user' . $i,
                'full_name' => 'User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'password' => 'password',
                'role' => 'customer',
                'address' => $i . '00 Customer St',
                'city' => 'Customer City',
                'contact' => '555-' . str_pad($i, 3, '0', STR_PAD_LEFT) . '-' . rand(1000, 9999),
            ]);
        }
    }
}
