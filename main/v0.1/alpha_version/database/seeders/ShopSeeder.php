<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $shopOwners = User::role('shop')->get();
        $cities = ['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة'];
        $regions = ['الرياض', 'مكة المكرمة', 'الشرقية', 'المدينة المنورة', 'القصيم'];
        
        $shopNames = [
            'متجر قطع الغيار الأصلي',
            'مركز قطع الغيار المتقدم',
            'متجر السيارات الذكي',
            'مركز الصيانة الشامل',
            'متجر قطع الغيار المميز',
            'مركز الخدمات السريعة',
            'متجر قطع الغيار الجديد',
            'مركز الصيانة المتخصص',
            'متجر السيارات الأصلي',
            'مركز قطع الغيار الشامل',
        ];

        foreach ($shopOwners as $index => $owner) {
            $shopName = $shopNames[$index % count($shopNames)] . ' ' . ($index + 1);
            
            Shop::create([
                'user_id' => $owner->id,
                'name' => $shopName,
                'name_en' => 'Auto Parts Shop ' . ($index + 1),
                'slug' => Str::slug($shopName),
                'description' => 'متجر متخصص في قطع غيار السيارات الأصلية والبديلة',
                'description_en' => 'Specialized shop in original and alternative auto parts',
                'phone' => '05' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'email' => $owner->email,
                'address' => 'شارع ' . rand(1, 100) . '، حي ' . rand(1, 50),
                'city' => $cities[array_rand($cities)],
                'region' => $regions[array_rand($regions)],
                'postal_code' => rand(10000, 99999),
                'latitude' => 24.7136 + (rand(-100, 100) / 1000),
                'longitude' => 46.6753 + (rand(-100, 100) / 1000),
                'business_hours' => [
                    'saturday' => '08:00-22:00',
                    'sunday' => '08:00-22:00',
                    'monday' => '08:00-22:00',
                    'tuesday' => '08:00-22:00',
                    'wednesday' => '08:00-22:00',
                    'thursday' => '08:00-22:00',
                    'friday' => 'closed',
                ],
                'payment_methods' => ['cash', 'card', 'mada', 'stc_pay'],
                'delivery_options' => ['local', 'express', 'standard'],
                'is_verified' => rand(0, 1),
                'is_active' => true,
                'is_featured' => rand(0, 1),
                'rating_average' => rand(35, 50) / 10,
                'rating_count' => rand(10, 100),
                'total_orders' => rand(50, 500),
                'total_revenue' => rand(10000, 100000),
                'commission_rate' => rand(8, 15),
                'tax_number' => '3' . str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT),
                'cr_number' => str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT),
            ]);
        }
    }
} 