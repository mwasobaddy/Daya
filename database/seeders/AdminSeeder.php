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
        // Admin users to create/update
        $admins = [
            [
                'name' => 'Kelvin Ramsiel',
                'email' => 'kelvinramsiel@gmail.com',
                'phone' => '+1234567890',
                'referral_code' => '7a3H4P',
                'title' => 'System Administrator',
                'department' => 'IT',
                'role' => 'admin',
            ],
            [
                'name' => 'Daya',
                'email' => 'company@example.com',
                'phone' => '+1234567892',
                'referral_code' => '9c5R6S',
                'title' => 'Company Profile',
                'department' => 'Company',
                'role' => 'company',
            ],
            // [
            //     'name' => 'Akinola Dixon',
            //     'email' => 'akinola.dixon@gmail.com',
            //     'phone' => '+1234567891',
            //     'referral_code' => '8b4I5Q',
            //     'title' => 'System Administrator',
            //     'department' => 'Operations',
            // ],
        ];

        foreach ($admins as $adminData) {
            $role = $adminData['role'] ?? 'admin';
            
            // Update existing user or create new admin
            $admin = User::where('email', $adminData['email'])->first();

            if ($admin) {
                // Update existing user to admin role
                $admin->update([
                    'role' => $role,
                    'wallet_status' => 'activated',
                    'profile' => array_merge($admin->profile ?? [], [
                        'title' => $adminData['title'],
                        'department' => $adminData['department'],
                        'permissions' => ['all'],
                        'updated_by' => 'seeder',
                        'updated_at' => now(),
                    ]),
                ]);
            } else {
                // Create new admin user
                User::create([
                    'name' => $adminData['name'],
                    'email' => $adminData['email'],
                    'password' => Hash::make('password123'), // You should change this to a secure password
                    'role' => $role,
                    'phone' => $adminData['phone'],
                    'referral_code' => $adminData['referral_code'],
                    'ward_id' => 1, // Default to first ward
                    'email_verified_at' => now(),
                    'wallet_status' => 'activated',
                    'profile' => [
                        'title' => $adminData['title'],
                        'department' => $adminData['department'],
                        'permissions' => ['all'],
                        'created_by' => 'system',
                    ],
                ]);
            }
        }
    }
}
