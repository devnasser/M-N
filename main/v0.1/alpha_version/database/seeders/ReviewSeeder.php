<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Driver;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $buyers = User::role('buyer')->get();
        $products = Product::all();
        $shops = Shop::all();
        $drivers = Driver::all();
        $technicians = Technician::all();
        
        $comments = [
            'منتج ممتاز وجودة عالية',
            'سعر مناسب وجودة جيدة',
            'توصيل سريع وممتاز',
            'خدمة عملاء ممتازة',
            'منتج أصلي ومضمون',
            'سعر مرتفع قليلاً لكن الجودة ممتازة',
            'توصيل بطيء قليلاً',
            'منتج جيد لكن السعر مرتفع',
            'خدمة ممتازة وسرعة في التوصيل',
            'منتج عالي الجودة',
        ];
        
        // Product Reviews
        foreach ($products->random(200) as $product) {
            Review::create([
                'user_id' => $buyers->random()->id,
                'reviewable_type' => Product::class,
                'reviewable_id' => $product->id,
                'rating' => rand(3, 5),
                'comment' => $comments[array_rand($comments)],
                'comment_en' => 'Great product and high quality',
                'status' => 'approved',
                'is_verified' => rand(0, 1),
                'is_helpful' => rand(0, 1),
            ]);
        }
        
        // Shop Reviews
        foreach ($shops->random(min(20, $shops->count())) as $shop) {
            Review::create([
                'user_id' => $buyers->random()->id,
                'reviewable_type' => Shop::class,
                'reviewable_id' => $shop->id,
                'rating' => rand(3, 5),
                'comment' => $comments[array_rand($comments)],
                'comment_en' => 'Excellent service and fast delivery',
                'status' => 'approved',
                'is_verified' => rand(0, 1),
                'is_helpful' => rand(0, 1),
            ]);
        }
        
        // Driver Reviews
        foreach ($drivers->random(min(30, $drivers->count())) as $driver) {
            Review::create([
                'user_id' => $buyers->random()->id,
                'reviewable_type' => Driver::class,
                'reviewable_id' => $driver->id,
                'rating' => rand(3, 5),
                'comment' => $comments[array_rand($comments)],
                'comment_en' => 'Fast and professional delivery',
                'status' => 'approved',
                'is_verified' => rand(0, 1),
                'is_helpful' => rand(0, 1),
            ]);
        }
        
        // Technician Reviews
        foreach ($technicians->random(min(25, $technicians->count())) as $technician) {
            Review::create([
                'user_id' => $buyers->random()->id,
                'reviewable_type' => Technician::class,
                'reviewable_id' => $technician->id,
                'rating' => rand(3, 5),
                'comment' => $comments[array_rand($comments)],
                'comment_en' => 'Professional and skilled technician',
                'status' => 'approved',
                'is_verified' => rand(0, 1),
                'is_helpful' => rand(0, 1),
            ]);
        }
    }
} 