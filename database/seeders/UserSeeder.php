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
    }
}
