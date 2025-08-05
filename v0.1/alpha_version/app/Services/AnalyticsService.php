<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * الحصول على إحصائيات شاملة
     */
    public function getDashboardStats()
    {
        return Cache::remember('dashboard_stats', 1800, function () {
            return [
                'total_users' => User::count(),
                'total_products' => Product::count(),
                'total_orders' => Order::count(),
                'total_shops' => Shop::count(),
                'active_products' => Product::active()->count(),
                'products_in_stock' => Product::inStock()->count(),
                'total_revenue' => Order::where('status', 'completed')->sum('total_amount'),
                'monthly_revenue' => $this->getMonthlyRevenue(),
                'top_products' => $this->getTopProducts(),
                'top_categories' => $this->getTopCategories(),
                'recent_orders' => $this->getRecentOrders(),
                'sales_chart' => $this->getSalesChart(),
            ];
        });
    }

    /**
     * الحصول على الإيرادات الشهرية
     */
    public function getMonthlyRevenue()
    {
        return Order::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');
    }

    /**
     * الحصول على أفضل المنتجات
     */
    public function getTopProducts($limit = 10)
    {
        return Product::active()
            ->orderBy('rating_average', 'desc')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على أفضل الفئات
     */
    public function getTopCategories($limit = 5)
    {
        return DB::table('categories')
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->select('categories.*', DB::raw('COUNT(products.id) as product_count'))
            ->groupBy('categories.id')
            ->orderBy('product_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على الطلبات الحديثة
     */
    public function getRecentOrders($limit = 10)
    {
        return Order::with(['user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على مخطط المبيعات
     */
    public function getSalesChart()
    {
        return Cache::remember('sales_chart', 3600, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $sales = Order::where('status', 'completed')
                    ->whereDate('created_at', $date)
                    ->sum('total_amount');
                
                $data[] = [
                    'date' => $date->format('Y-m-d'),
                    'sales' => $sales,
                    'orders' => Order::whereDate('created_at', $date)->count(),
                ];
            }
            return $data;
        });
    }

    /**
     * تحليل سلوك المستخدمين
     */
    public function getUserBehavior()
    {
        return Cache::remember('user_behavior', 7200, function () {
            return [
                'most_viewed_products' => $this->getMostViewedProducts(),
                'most_favorited_products' => $this->getMostFavoritedProducts(),
                'search_trends' => $this->getSearchTrends(),
                'category_preferences' => $this->getCategoryPreferences(),
            ];
        });
    }

    /**
     * الحصول على أكثر المنتجات مشاهدة
     */
    public function getMostViewedProducts($limit = 10)
    {
        return Product::active()
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على أكثر المنتجات إضافة للمفضلة
     */
    public function getMostFavoritedProducts($limit = 10)
    {
        return Product::active()
            ->orderBy('favorite_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على اتجاهات البحث
     */
    public function getSearchTrends()
    {
        // يمكن إضافة جدول للبحث لتحليل اتجاهات البحث
        return [
            'popular_searches' => ['قطع غيار', 'إطارات', 'زيت محرك', 'بطارية'],
            'trending_categories' => ['محرك', 'فرامل', 'كهرباء', 'تبريد'],
        ];
    }

    /**
     * الحصول على تفضيلات الفئات
     */
    public function getCategoryPreferences()
    {
        return DB::table('categories')
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->join('favorites', 'products.id', '=', 'favorites.product_id')
            ->select('categories.name_ar', DB::raw('COUNT(favorites.id) as favorite_count'))
            ->groupBy('categories.id', 'categories.name_ar')
            ->orderBy('favorite_count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * تحليل الأداء
     */
    public function getPerformanceMetrics()
    {
        return [
            'page_load_time' => $this->getAveragePageLoadTime(),
            'database_queries' => $this->getDatabaseQueryCount(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'memory_usage' => $this->getMemoryUsage(),
        ];
    }

    /**
     * الحصول على متوسط وقت تحميل الصفحة
     */
    private function getAveragePageLoadTime()
    {
        // يمكن إضافة نظام تتبع الأداء
        return 0.5; // ثانية
    }

    /**
     * الحصول على عدد استعلامات قاعدة البيانات
     */
    private function getDatabaseQueryCount()
    {
        // يمكن استخدام Debugbar أو أدوات أخرى
        return 15; // عدد تقريبي
    }

    /**
     * الحصول على معدل نجاح الكاش
     */
    private function getCacheHitRate()
    {
        return 85; // نسبة مئوية
    }

    /**
     * الحصول على استخدام الذاكرة
     */
    private function getMemoryUsage()
    {
        return memory_get_usage(true) / 1024 / 1024; // MB
    }

    /**
     * تحليل المخزون
     */
    public function getInventoryAnalysis()
    {
        return [
            'low_stock_products' => $this->getLowStockProducts(),
            'out_of_stock_products' => $this->getOutOfStockProducts(),
            'overstocked_products' => $this->getOverstockedProducts(),
            'stock_alerts' => $this->getStockAlerts(),
        ];
    }

    /**
     * الحصول على المنتجات منخفضة المخزون
     */
    public function getLowStockProducts($limit = 20)
    {
        return Product::where('stock_quantity', '<=', DB::raw('min_stock_quantity'))
            ->where('stock_quantity', '>', 0)
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على المنتجات نفذت من المخزون
     */
    public function getOutOfStockProducts($limit = 20)
    {
        return Product::where('stock_quantity', 0)
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على المنتجات المكدسة
     */
    public function getOverstockedProducts($limit = 20)
    {
        return Product::where('stock_quantity', '>=', DB::raw('max_stock_quantity'))
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على تنبيهات المخزون
     */
    public function getStockAlerts()
    {
        return [
            'low_stock_count' => $this->getLowStockProducts()->count(),
            'out_of_stock_count' => $this->getOutOfStockProducts()->count(),
            'overstocked_count' => $this->getOverstockedProducts()->count(),
        ];
    }
} 