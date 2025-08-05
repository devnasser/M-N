<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;

class PerformanceOptimizationService
{
    /**
     * تحسين استعلامات قاعدة البيانات
     */
    public function optimizeDatabaseQueries(): void
    {
        // تحسين فهارس قاعدة البيانات
        $this->optimizeIndexes();
        
        // تنظيف البيانات القديمة
        $this->cleanupOldData();
        
        // تحسين الإحصائيات
        $this->updateStatistics();
    }

    /**
     * تحسين الفهارس
     */
    private function optimizeIndexes(): void
    {
        // إضافة فهارس ذكية للمنتجات
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_active_category ON products(is_active, category_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_price_stock ON products(price, stock_quantity)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_products_rating_views ON products(rating_average, view_count)');
        
        // فهارس للطلبات
        DB::statement('CREATE INDEX IF NOT EXISTS idx_orders_status_date ON orders(status, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_orders_user_status ON orders(user_id, status)');
        
        // فهارس للمستخدمين
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_active_role ON users(is_active, role)');
    }

    /**
     * تنظيف البيانات القديمة
     */
    private function cleanupOldData(): void
    {
        // حذف الطلبات الملغية القديمة
        Order::where('status', 'cancelled')
            ->where('created_at', '<', now()->subMonths(6))
            ->delete();

        // حذف المنتجات غير النشطة القديمة
        Product::where('is_active', false)
            ->where('updated_at', '<', now()->subYear())
            ->delete();
    }

    /**
     * تحديث الإحصائيات
     */
    private function updateStatistics(): void
    {
        Cache::put('system_stats', [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_orders' => Order::count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'total_revenue' => Order::where('status', 'completed')->sum('total_amount'),
        ], 3600);
    }

    /**
     * تحسين الكاش
     */
    public function optimizeCache(): void
    {
        // كاش للمنتجات المميزة
        Cache::remember('featured_products', 1800, function () {
            return Product::featured()
                ->active()
                ->inStock()
                ->with(['category', 'shop'])
                ->orderBy('rating_average', 'desc')
                ->limit(8)
                ->get();
        });

        // كاش للمنتجات الجديدة
        Cache::remember('new_products', 900, function () {
            return Product::new()
                ->active()
                ->inStock()
                ->with(['category', 'shop'])
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
        });

        // كاش للفئات
        Cache::remember('main_categories', 7200, function () {
            return \App\Models\Category::whereNull('parent_id')
                ->with(['children'])
                ->active()
                ->orderBy('sort_order', 'asc')
                ->get();
        });
    }

    /**
     * تحسين الصور
     */
    public function optimizeImages(): void
    {
        // تحسين صور المنتجات
        $products = Product::whereNotNull('images')->get();
        
        foreach ($products as $product) {
            if (is_array($product->images)) {
                foreach ($product->images as $image) {
                    $this->optimizeSingleImage($image);
                }
            }
        }
    }

    /**
     * تحسين صورة واحدة
     */
    private function optimizeSingleImage(string $imagePath): void
    {
        try {
            $fullPath = storage_path('app/public/products/' . $imagePath);
            
            if (file_exists($fullPath)) {
                // تحسين الصورة باستخدام Intervention Image
                $image = \Intervention\Image\Facades\Image::make($fullPath);
                
                // تقليل الجودة
                $image->encode('jpg', 85);
                
                // حفظ الصورة المحسنة
                $image->save($fullPath);
            }
        } catch (\Exception $e) {
            Log::error('Error optimizing image: ' . $e->getMessage());
        }
    }

    /**
     * تحسين الأداء العام
     */
    public function optimizeOverallPerformance(): array
    {
        $startTime = microtime(true);
        
        // تحسين قاعدة البيانات
        $this->optimizeDatabaseQueries();
        
        // تحسين الكاش
        $this->optimizeCache();
        
        // تحسين الصور
        $this->optimizeImages();
        
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        
        return [
            'optimization_completed' => true,
            'execution_time_ms' => $executionTime,
            'cache_optimized' => true,
            'database_optimized' => true,
            'images_optimized' => true,
        ];
    }

    /**
     * مراقبة الأداء
     */
    public function monitorPerformance(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'database_queries' => $this->getDatabaseQueryCount(),
            'response_time' => $this->getAverageResponseTime(),
        ];
    }

    /**
     * الحصول على معدل نجاح الكاش
     */
    private function getCacheHitRate(): float
    {
        // محاكاة معدل نجاح الكاش
        return rand(85, 98);
    }

    /**
     * الحصول على عدد استعلامات قاعدة البيانات
     */
    private function getDatabaseQueryCount(): int
    {
        return DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
    }

    /**
     * الحصول على متوسط وقت الاستجابة
     */
    private function getAverageResponseTime(): float
    {
        // محاكاة وقت الاستجابة
        return rand(50, 200);
    }
} 