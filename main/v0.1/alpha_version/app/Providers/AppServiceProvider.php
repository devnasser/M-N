<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AnalyticsService;
use App\Services\RecommendationService;
use App\Services\PerformanceOptimizationService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // تسجيل الخدمات المتقدمة
        $this->app->singleton(AnalyticsService::class);
        $this->app->singleton(RecommendationService::class);
        $this->app->singleton(PerformanceOptimizationService::class);
    }

    public function boot(): void
    {
        // تحسين الأداء العام
        $this->optimizePerformance();
        
        // إعداد الكاش المتقدم
        $this->setupAdvancedCaching();
        
        // مراقبة الأداء
        $this->setupPerformanceMonitoring();
    }

    private function optimizePerformance(): void
    {
        // تحسين استعلامات قاعدة البيانات
        DB::whenQueryingForLongerThan(5000, function ($connection) {
            Log::warning('Slow query detected', [
                'connection' => $connection->getName(),
                'queries' => $connection->getQueryLog(),
            ]);
        });

        // تحسين الكاش
        Cache::extend('redis', function ($app) {
            return Cache::repository(new \Illuminate\Cache\RedisStore(
                $app['redis'],
                config('cache.prefix')
            ));
        });
    }

    private function setupAdvancedCaching(): void
    {
        // كاش ذكي للمنتجات
        Cache::remember('system_stats', 3600, function () {
            return [
                'total_products' => \App\Models\Product::count(),
                'total_users' => \App\Models\User::count(),
                'total_orders' => \App\Models\Order::count(),
                'total_revenue' => \App\Models\Order::where('status', 'completed')->sum('total_amount'),
            ];
        });
    }

    private function setupPerformanceMonitoring(): void
    {
        // مراقبة الأداء في الوقت الفعلي
        if (config('app.debug')) {
            DB::listen(function ($query) {
                if ($query->time > 100) {
                    Log::info('Slow query', [
                        'sql' => $query->sql,
                        'time' => $query->time,
                        'bindings' => $query->bindings,
                    ]);
                }
            });
        }
    }
}
