<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing user or create new admin
        $admin = User::where('email', 'kelvinramsiel@gmail.com')->first();

        if ($admin) {
            // Update existing user to admin role
            $admin->update([
                'role' => 'admin',
                'wallet_status' => 'activated',
                'profile' => array_merge($admin->profile ?? [], [
                    'title' => 'System Administrator',
                    'department' => 'IT',
                    'permissions' => ['all'],
                    'updated_by' => 'seeder',
                    'updated_at' => now(),
                ]),
            ]);
        } else {
            // Create new admin user
            User::create([
                'name' => 'Kelvin Ramsiel',
                'email' => 'kelvinramsiel@gmail.com',
                'password' => Hash::make('password123'), // You should change this to a secure password
                'role' => 'admin',
                'phone' => '+1234567890',
                'country' => 'Kenya',
                'email_verified_at' => now(),
                'wallet_status' => 'activated',
                'profile' => [
                    'title' => 'System Administrator',
                    'department' => 'IT',
                    'permissions' => ['all'],
                    'created_by' => 'system',
                ],
            ]);
        }
    }
}
