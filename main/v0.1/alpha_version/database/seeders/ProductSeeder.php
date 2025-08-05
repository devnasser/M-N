<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::whereNotNull('parent_id')->get();
        $shops = Shop::all();
        $brands = ['تويوتا', 'هوندا', 'نيسان', 'فورد', 'شيفروليه', 'BMW', 'مرسيدس', 'أودي', 'فولكس فاجن', 'هيونداي'];
        
        $productNames = [
            'زيت محرك عالي الجودة',
            'فلتر زيت أصلي',
            'شمعات إشعال بلاتينية',
            'حزام محرك مطاطي',
            'بطانة فرامل خلفية',
            'أقراص فرامل أمامية',
            'سائل فرامل DOT4',
            'ينابيع تعليق خلفية',
            'ممتص صدمات أمامي',
            'مفصل كرة علوي',
            'مضخة ماء محرك',
            'مروحة تبريد كهربائية',
            'مبرد محرك ألمنيوم',
            'بطارية سيارة 60 أمبير',
            'مولد كهربائي 90 أمبير',
            'مصابيح أمامية LED',
            'مضخة وقود كهربائية',
            'فلتر وقود أصلي',
            'حاقن وقود إلكتروني',
        ];

        for ($i = 1; $i <= 1000; $i++) {
            $category = $categories->random();
            $shop = $shops->random();
            $brand = $brands[array_rand($brands)];
            $productName = $productNames[array_rand($productNames)];
            
            $price = rand(50, 2000);
            $salePrice = rand(0, 1) ? $price * 0.8 : null;
            
            Product::create([
                'name' => $productName . ' ' . $brand,
                'name_en' => $productName . ' ' . $brand,
                'slug' => Str::slug($productName . ' ' . $brand . ' ' . $i),
                'description' => 'قطع غيار عالية الجودة مناسبة لجميع أنواع السيارات',
                'description_en' => 'High quality auto parts suitable for all types of vehicles',
                'short_description' => 'قطع غيار أصلية',
                'short_description_en' => 'Original parts',
                'sku' => 'SKU' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'barcode' => 'BAR' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'brand' => $brand,
                'model' => 'موديل ' . rand(2010, 2024),
                'year_from' => rand(2010, 2020),
                'year_to' => rand(2021, 2024),
                'category_id' => $category->id,
                'shop_id' => $shop->id,
                'price' => $price,
                'sale_price' => $salePrice,
                'cost_price' => $price * 0.6,
                'weight' => rand(100, 5000) / 100,
                'dimensions' => [
                    'length' => rand(10, 50),
                    'width' => rand(10, 30),
                    'height' => rand(5, 20),
                ],
                'stock_quantity' => rand(0, 100),
                'min_stock_quantity' => 5,
                'max_stock_quantity' => 200,
                'is_active' => rand(0, 10) > 1,
                'is_featured' => rand(0, 10) > 7,
                'is_bestseller' => rand(0, 10) > 8,
                'is_new' => rand(0, 10) > 8,
                'is_on_sale' => $salePrice ? true : false,
                'sale_start_date' => $salePrice ? now()->subDays(rand(1, 30)) : null,
                'sale_end_date' => $salePrice ? now()->addDays(rand(1, 60)) : null,
                'images' => ['products/product' . rand(1, 10) . '.jpg'],
                'specifications' => [
                    'المادة' => 'معدن/بلاستيك',
                    'اللون' => 'أسود/أبيض',
                    'الحجم' => 'قياسي',
                ],
                'compatibility' => [$brand, 'عام'],
                'warranty_period' => rand(6, 24) . ' شهر',
                'warranty_type' => 'ضمان شامل',
                'return_policy' => 'إمكانية الإرجاع خلال 14 يوم',
                'shipping_info' => 'شحن مجاني للطلبات فوق 200 ريال',
                'view_count' => rand(0, 1000),
                'favorite_count' => rand(0, 50),
                'rating_average' => rand(35, 50) / 10,
                'rating_count' => rand(0, 100),
            ]);
        }
    }
} 