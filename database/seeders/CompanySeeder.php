<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the company user required for scan earnings distribution
        $companyData = [
            'name' => 'Daya',
            'email' => 'company@example.com',
            'phone' => '0111984608',
            'referral_code' => '9c5R6S',
            'title' => 'Company Profile',
            'department' => 'Company',
            'role' => 'company',
        ];

        // Check if company user already exists
        $company = User::where('email', $companyData['email'])->first();

        if ($company) {
            // Update existing user to ensure correct role and data
            $company->update([
                'name' => $companyData['name'],
                'role' => $companyData['role'],
                'phone' => $companyData['phone'],
                'referral_code' => $companyData['referral_code'],
                'wallet_status' => 'activated',
                'profile' => array_merge($company->profile ?? [], [
                    'title' => $companyData['title'],
                    'department' => $companyData['department'],
                    'permissions' => ['all'],
                    'updated_by' => 'seeder',
                    'updated_at' => now(),
                ]),
            ]);
            $this->command->info('Company user updated: ' . $company->name);
        } else {
            // Get first ward for location data
            $ward = \App\Models\Ward::first();

            // Create new company user
            $company = User::create([
                'name' => $companyData['name'],
                'email' => $companyData['email'],
                'password' => Hash::make('password123'), // Default password
                'role' => $companyData['role'],
                'phone' => $companyData['phone'],
                'referral_code' => $companyData['referral_code'],
                'ward_id' => $ward ? $ward->id : 1,
                'email_verified_at' => now(),
                'wallet_status' => 'activated',
                'profile' => [
                    'title' => $companyData['title'],
                    'department' => $companyData['department'],
                    'permissions' => ['all'],
                    'created_by' => 'system',
                ],
            ]);
            $this->command->info('Company user created: ' . $company->name . ' (ID: ' . $company->id . ')');
        }
    }
}