<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'academics.johnfritzcabalhin@gmail.com'], // Check if this email exists
            [
                'name' => 'Senco Admin',
                'password' => Hash::make('@SENCO2026'), // Encrypts the password
                'email_verified_at' => now(),
                'role' => 'Admin',
            ]
        );
    }
}