<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        $drivers = User::role('driver')->get();
        $vehicleTypes = ['سيارة', 'دراجة نارية', 'شاحنة صغيرة'];
        $vehicleModels = ['تويوتا كامري', 'هوندا سيفيك', 'نيسان سنترا', 'فورد فوكس', 'شيفروليه كروز'];
        
        foreach ($drivers as $driver) {
            Driver::create([
                'user_id' => $driver->id,
                'vehicle_type' => $vehicleTypes[array_rand($vehicleTypes)],
                'vehicle_model' => $vehicleModels[array_rand($vehicleModels)],
                'vehicle_year' => rand(2015, 2023),
                'license_number' => 'DL' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'license_expiry' => now()->addYears(rand(1, 5)),
                'insurance_number' => 'INS' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'insurance_expiry' => now()->addYears(rand(1, 3)),
                'latitude' => 24.7136 + (rand(-100, 100) / 1000),
                'longitude' => 46.6753 + (rand(-100, 100) / 1000),
                'is_available' => rand(0, 1),
                'is_verified' => rand(0, 1),
                'is_active' => true,
                'rating_average' => rand(35, 50) / 10,
                'rating_count' => rand(5, 50),
                'total_deliveries' => rand(10, 200),
                'total_earnings' => rand(1000, 50000),
                'commission_rate' => rand(10, 20),
                'preferred_areas' => [
                    ['latitude' => 24.7136, 'longitude' => 46.6753, 'radius' => 10],
                ],
                'working_hours' => [
                    'saturday' => '08:00-22:00',
                    'sunday' => '08:00-22:00',
                    'monday' => '08:00-22:00',
                    'tuesday' => '08:00-22:00',
                    'wednesday' => '08:00-22:00',
                    'thursday' => '08:00-22:00',
                    'friday' => 'closed',
                ],
            ]);
        }
    }
} 