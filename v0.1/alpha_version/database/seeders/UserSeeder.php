<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone' => '0501234567',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // Buyers
        for ($i = 1; $i <= 50; $i++) {
            $buyer = User::create([
                'name' => "مشتري {$i}",
                'email' => "buyer{$i}@example.com",
                'password' => Hash::make('password'),
                'phone' => '05' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'city' => ['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة'][array_rand(['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة'])],
                'region' => ['الرياض', 'مكة المكرمة', 'الشرقية', 'المدينة المنورة', 'القصيم'][array_rand(['الرياض', 'مكة المكرمة', 'الشرقية', 'المدينة المنورة', 'القصيم'])],
                'is_verified' => rand(0, 1),
                'is_active' => true,
            ]);
            $buyer->assignRole('buyer');
        }

        // Shop Owners
        for ($i = 1; $i <= 20; $i++) {
            $shopOwner = User::create([
                'name' => "صاحب متجر {$i}",
                'email' => "shop{$i}@example.com",
                'password' => Hash::make('password'),
                'phone' => '05' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'city' => ['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة'][array_rand(['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة'])],
                'region' => ['الرياض', 'مكة المكرمة', 'الشرقية', 'المدينة المنورة', 'القصيم'][array_rand(['الرياض', 'مكة المكرمة', 'الشرقية', 'المدينة المنورة', 'القصيم'])],
                'is_verified' => rand(0, 1),
                'is_active' => true,
            ]);
            $shopOwner->assignRole('shop');
        }

        // Drivers
        for ($i = 1; $i <= 30; $i++) {
            $driver = User::create([
                'name' => "موصل {$i}",
                'email' => "driver{$i}@example.com",
                'password' => Hash::make('password'),
                'phone' => '05' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'city' => ['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة'][array_rand(['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة'])],
                'region' => ['الرياض', 'مكة المكرمة', 'الشرقية', 'المدينة المنورة', 'القصيم'][array_rand(['الرياض', 'مكة المكرمة', 'الشرقية', 'المدينة المنورة', 'القصيم'])],
                'is_verified' => rand(0, 1),
                'is_active' => true,
            ]);
            $driver->assignRole('driver');
        }

        // Technicians
        for ($i = 1; $i <= 25; $i++) {
            $technician = User::create([
                'name' => "فني {$i}",
                'email' => "technician{$i}@example.com",
                'password' => Hash::make('password'),
                'phone' => '05' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'city' => ['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة'][array_rand(['الرياض', 'جدة', 'الدمام', 'مكة', 'المدينة'])],
                'region' => ['الرياض', 'مكة المكرمة', 'الشرقية', 'المدينة المنورة', 'القصيم'][array_rand(['الرياض', 'مكة المكرمة', 'الشرقية', 'المدينة المنورة', 'القصيم'])],
                'is_verified' => rand(0, 1),
                'is_active' => true,
            ]);
            $technician->assignRole('technician');
        }
    }
} 