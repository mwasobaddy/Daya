<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\County;
use App\Models\Subcounty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GeographicalDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $geographicalData = [
            [
                'code' => 'KE',
                'name' => 'Kenya',
                'county_label' => 'County',
                'subcounty_label' => 'Sub-County',
                'counties' => [
                    [
                        'name' => 'Nairobi',
                        'subcounties' => ['Westlands', 'CBD', 'Parklands', 'Karen', 'Langata']
                    ],
                    [
                        'name' => 'Mombasa',
                        'subcounties' => ['Mvita', 'Changamwe', 'Jomvu', 'Likoni', 'Nyali']
                    ],
                    [
                        'name' => 'Kisumu',
                        'subcounties' => ['Kisumu Central', 'Kisumu East', 'Kisumu West', 'Muhoroni', 'Nyando']
                    ],
                    [
                        'name' => 'Nakuru',
                        'subcounties' => ['Nakuru Town East', 'Nakuru Town West', 'Njoro', 'Rongai', 'Subukia']
                    ],
                    [
                        'name' => 'Eldoret',
                        'subcounties' => ['Soy', 'Turbo', 'Moiben', 'Kwanza', 'Endebess']
                    ]
                ]
            ],
            [
                'code' => 'NG',
                'name' => 'Nigeria',
                'county_label' => 'State',
                'subcounty_label' => 'Local Government Area',
                'counties' => [
                    [
                        'name' => 'Lagos',
                        'subcounties' => ['Ikeja', 'Surulere', 'Lagos Island', 'Eti-Osa', 'Apapa']
                    ],
                    [
                        'name' => 'Abuja',
                        'subcounties' => ['Abuja Municipal', 'Gwagwalada', 'Kuje', 'Bwari', 'Kwali']
                    ],
                    [
                        'name' => 'Kano',
                        'subcounties' => ['Kano Municipal', 'Fagge', 'Dala', 'Gwale', 'Tarauni']
                    ],
                    [
                        'name' => 'Rivers',
                        'subcounties' => ['Port Harcourt', 'Obio-Akpor', 'Ikwerre', 'Emohua', 'Etche']
                    ],
                    [
                        'name' => 'Oyo',
                        'subcounties' => ['Ibadan North', 'Ibadan South-East', 'Ibadan South-West', 'Ibadan North-East', 'Ibadan North-West']
                    ]
                ]
            ]
        ];

        foreach ($geographicalData as $countryData) {
            $country = Country::create([
                'code' => $countryData['code'],
                'name' => $countryData['name'],
                'county_label' => $countryData['county_label'],
                'subcounty_label' => $countryData['subcounty_label'],
            ]);

            foreach ($countryData['counties'] as $countyData) {
                $county = County::create([
                    'country_id' => $country->id,
                    'name' => $countyData['name'],
                ]);

                foreach ($countyData['subcounties'] as $subcountyName) {
                    Subcounty::create([
                        'county_id' => $county->id,
                        'name' => $subcountyName,
                    ]);
                }
            }
        }
    }
}
