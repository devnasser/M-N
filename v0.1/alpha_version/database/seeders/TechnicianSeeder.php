<?php

namespace Database\Seeders;

use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;

class TechnicianSeeder extends Seeder
{
    public function run(): void
    {
        $technicians = User::role('technician')->get();
        $specializations = [
            ['ميكانيكا', 'كهرباء', 'إلكترونيات'],
            ['ميكانيكا', 'فرامل', 'تعليق'],
            ['كهرباء', 'إلكترونيات', 'تكييف'],
            ['ميكانيكا', 'تبريد', 'وقود'],
            ['كهرباء', 'فرامل', 'تعليق'],
        ];
        
        foreach ($technicians as $technician) {
            Technician::create([
                'user_id' => $technician->id,
                'specializations' => $specializations[array_rand($specializations)],
                'certifications' => ['شهادة فني معتمد', 'شهادة صيانة سيارات'],
                'experience_years' => rand(2, 15),
                'hourly_rate' => rand(50, 200),
                'is_available' => rand(0, 1),
                'is_verified' => rand(0, 1),
                'is_active' => true,
                'rating_average' => rand(35, 50) / 10,
                'rating_count' => rand(5, 50),
                'total_appointments' => rand(10, 100),
                'total_earnings' => rand(5000, 50000),
                'commission_rate' => rand(15, 25),
                'working_hours' => [
                    'saturday' => '08:00-18:00',
                    'sunday' => '08:00-18:00',
                    'monday' => '08:00-18:00',
                    'tuesday' => '08:00-18:00',
                    'wednesday' => '08:00-18:00',
                    'thursday' => '08:00-18:00',
                    'friday' => 'closed',
                ],
                'service_areas' => ['الرياض', 'جدة', 'الدمام'],
                'bio' => 'فني متخصص في صيانة السيارات مع خبرة طويلة في المجال',
                'bio_en' => 'Specialized technician in car maintenance with long experience in the field',
            ]);
        }
    }
} 