<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'محرك',
                'name_en' => 'Engine',
                'icon' => 'fas fa-cog',
                'color' => '#dc3545',
                'children' => [
                    ['name' => 'زيت المحرك', 'name_en' => 'Engine Oil', 'icon' => 'fas fa-oil-can'],
                    ['name' => 'فلتر الزيت', 'name_en' => 'Oil Filter', 'icon' => 'fas fa-filter'],
                    ['name' => 'شمعات الإشعال', 'name_en' => 'Spark Plugs', 'icon' => 'fas fa-bolt'],
                    ['name' => 'حزام المحرك', 'name_en' => 'Engine Belt', 'icon' => 'fas fa-circle'],
                ]
            ],
            [
                'name' => 'نظام الفرامل',
                'name_en' => 'Brake System',
                'icon' => 'fas fa-stop-circle',
                'color' => '#fd7e14',
                'children' => [
                    ['name' => 'بطانة الفرامل', 'name_en' => 'Brake Pads', 'icon' => 'fas fa-square'],
                    ['name' => 'أقراص الفرامل', 'name_en' => 'Brake Discs', 'icon' => 'fas fa-circle'],
                    ['name' => 'سائل الفرامل', 'name_en' => 'Brake Fluid', 'icon' => 'fas fa-tint'],
                ]
            ],
            [
                'name' => 'نظام التعليق',
                'name_en' => 'Suspension System',
                'icon' => 'fas fa-car',
                'color' => '#20c997',
                'children' => [
                    ['name' => 'الينابيع', 'name_en' => 'Springs', 'icon' => 'fas fa-wave-square'],
                    ['name' => 'ممتصات الصدمات', 'name_en' => 'Shock Absorbers', 'icon' => 'fas fa-compress-arrows-alt'],
                    ['name' => 'مفاصل الكرة', 'name_en' => 'Ball Joints', 'icon' => 'fas fa-circle'],
                ]
            ],
            [
                'name' => 'نظام التبريد',
                'name_en' => 'Cooling System',
                'icon' => 'fas fa-thermometer-half',
                'color' => '#0dcaf0',
                'children' => [
                    ['name' => 'مضخة الماء', 'name_en' => 'Water Pump', 'icon' => 'fas fa-tint'],
                    ['name' => 'مروحة التبريد', 'name_en' => 'Cooling Fan', 'icon' => 'fas fa-fan'],
                    ['name' => 'مبرد المحرك', 'name_en' => 'Radiator', 'icon' => 'fas fa-temperature-high'],
                ]
            ],
            [
                'name' => 'نظام الكهرباء',
                'name_en' => 'Electrical System',
                'icon' => 'fas fa-bolt',
                'color' => '#ffc107',
                'children' => [
                    ['name' => 'البطارية', 'name_en' => 'Battery', 'icon' => 'fas fa-car-battery'],
                    ['name' => 'المولد', 'name_en' => 'Alternator', 'icon' => 'fas fa-cog'],
                    ['name' => 'المصابيح', 'name_en' => 'Lights', 'icon' => 'fas fa-lightbulb'],
                ]
            ],
            [
                'name' => 'نظام الوقود',
                'name_en' => 'Fuel System',
                'icon' => 'fas fa-gas-pump',
                'color' => '#6f42c1',
                'children' => [
                    ['name' => 'مضخة الوقود', 'name_en' => 'Fuel Pump', 'icon' => 'fas fa-pump-soap'],
                    ['name' => 'فلتر الوقود', 'name_en' => 'Fuel Filter', 'icon' => 'fas fa-filter'],
                    ['name' => 'حاقنات الوقود', 'name_en' => 'Fuel Injectors', 'icon' => 'fas fa-tint'],
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);
            
            $category = Category::create([
                'name' => $categoryData['name'],
                'name_en' => $categoryData['name_en'],
                'slug' => Str::slug($categoryData['name_en']),
                'description' => 'تصنيف ' . $categoryData['name'],
                'description_en' => $categoryData['name_en'] . ' category',
                'icon' => $categoryData['icon'],
                'color' => $categoryData['color'],
                'is_active' => true,
            ]);

            foreach ($children as $childData) {
                Category::create([
                    'name' => $childData['name'],
                    'name_en' => $childData['name_en'],
                    'slug' => Str::slug($childData['name_en']),
                    'description' => 'تصنيف ' . $childData['name'],
                    'description_en' => $childData['name_en'] . ' category',
                    'icon' => $childData['icon'],
                    'parent_id' => $category->id,
                    'is_active' => true,
                ]);
            }
        }
    }
} 