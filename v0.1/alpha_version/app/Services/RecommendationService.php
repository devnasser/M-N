<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Favorite;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * الحصول على توصيات للمستخدم
     */
    public function getUserRecommendations($userId, $limit = 10)
    {
        return Cache::remember("user_recommendations_{$userId}", 3600, function () use ($userId, $limit) {
            $user = User::find($userId);
            if (!$user) {
                return $this->getPopularProducts($limit);
            }

            // تحليل سلوك المستخدم
            $userBehavior = $this->analyzeUserBehavior($user);
            
            // الحصول على التوصيات بناءً على السلوك
            $recommendations = $this->getRecommendationsByBehavior($userBehavior, $limit);
            
            // إذا لم تكن هناك توصيات كافية، أضف منتجات شائعة
            if ($recommendations->count() < $limit) {
                $popularProducts = $this->getPopularProducts($limit - $recommendations->count());
                $recommendations = $recommendations->merge($popularProducts);
            }

            return $recommendations->take($limit);
        });
    }

    /**
     * تحليل سلوك المستخدم
     */
    private function analyzeUserBehavior($user)
    {
        $favorites = $user->favorites()->with('category')->get();
        $orders = $user->orders()->with('items.product.category')->get();
        
        $categoryPreferences = [];
        $brandPreferences = [];
        $priceRange = [];

        // تحليل المفضلة
        foreach ($favorites as $favorite) {
            $categoryId = $favorite->category_id;
            $categoryPreferences[$categoryId] = ($categoryPreferences[$categoryId] ?? 0) + 1;
            
            $brand = $favorite->brand;
            if ($brand) {
                $brandPreferences[$brand] = ($brandPreferences[$brand] ?? 0) + 1;
            }
            
            $priceRange[] = $favorite->price;
        }

        // تحليل الطلبات
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $categoryId = $item->product->category_id;
                $categoryPreferences[$categoryId] = ($categoryPreferences[$categoryId] ?? 0) + 2; // وزن أعلى للطلبات
                
                $brand = $item->product->brand;
                if ($brand) {
                    $brandPreferences[$brand] = ($brandPreferences[$brand] ?? 0) + 2;
                }
                
                $priceRange[] = $item->product->price;
            }
        }

        return [
            'category_preferences' => $categoryPreferences,
            'brand_preferences' => $brandPreferences,
            'price_range' => $this->calculatePriceRange($priceRange),
            'total_interactions' => $favorites->count() + $orders->count(),
        ];
    }

    /**
     * حساب نطاق السعر المفضل
     */
    private function calculatePriceRange($prices)
    {
        if (empty($prices)) {
            return ['min' => 0, 'max' => 1000];
        }

        $avg = array_sum($prices) / count($prices);
        $std = $this->calculateStandardDeviation($prices);

        return [
            'min' => max(0, $avg - $std),
            'max' => $avg + $std,
            'avg' => $avg,
        ];
    }

    /**
     * حساب الانحراف المعياري
     */
    private function calculateStandardDeviation($array)
    {
        $avg = array_sum($array) / count($array);
        $variance = 0;
        
        foreach ($array as $value) {
            $variance += pow($value - $avg, 2);
        }
        
        return sqrt($variance / count($array));
    }

    /**
     * الحصول على التوصيات بناءً على السلوك
     */
    private function getRecommendationsByBehavior($behavior, $limit)
    {
        $query = Product::active()->inStock();

        // تصفية حسب الفئات المفضلة
        if (!empty($behavior['category_preferences'])) {
            $topCategories = array_keys($behavior['category_preferences']);
            $query->whereIn('category_id', $topCategories);
        }

        // تصفية حسب العلامات التجارية المفضلة
        if (!empty($behavior['brand_preferences'])) {
            $topBrands = array_keys($behavior['brand_preferences']);
            $query->whereIn('brand', $topBrands);
        }

        // تصفية حسب نطاق السعر
        if ($behavior['price_range']['min'] > 0 || $behavior['price_range']['max'] < 10000) {
            $query->whereBetween('price', [
                $behavior['price_range']['min'],
                $behavior['price_range']['max']
            ]);
        }

        return $query->orderBy('rating_average', 'desc')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على المنتجات الشائعة
     */
    public function getPopularProducts($limit = 10)
    {
        return Cache::remember("popular_products_{$limit}", 1800, function () use ($limit) {
            return Product::active()
                ->inStock()
                ->orderBy('view_count', 'desc')
                ->orderBy('rating_average', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * الحصول على المنتجات المماثلة
     */
    public function getSimilarProducts($productId, $limit = 6)
    {
        return Cache::remember("similar_products_{$productId}", 3600, function () use ($productId, $limit) {
            $product = Product::find($productId);
            if (!$product) {
                return collect();
            }

            return Product::where('id', '!=', $productId)
                ->where('category_id', $product->category_id)
                ->where('brand', $product->brand)
                ->active()
                ->inStock()
                ->orderBy('rating_average', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * الحصول على المنتجات المكملة
     */
    public function getComplementaryProducts($productId, $limit = 4)
    {
        return Cache::remember("complementary_products_{$productId}", 3600, function () use ($productId, $limit) {
            $product = Product::find($productId);
            if (!$product) {
                return collect();
            }

            // البحث عن منتجات في نفس الفئة ولكن علامة تجارية مختلفة
            return Product::where('id', '!=', $productId)
                ->where('category_id', $product->category_id)
                ->where('brand', '!=', $product->brand)
                ->active()
                ->inStock()
                ->orderBy('rating_average', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * الحصول على المنتجات المميزة
     */
    public function getFeaturedProducts($limit = 8)
    {
        return Cache::remember("featured_products_{$limit}", 1800, function () use ($limit) {
            return Product::featured()
                ->active()
                ->inStock()
                ->orderBy('rating_average', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * الحصول على المنتجات الجديدة
     */
    public function getNewProducts($limit = 8)
    {
        return Cache::remember("new_products_{$limit}", 1800, function () use ($limit) {
            return Product::new()
                ->active()
                ->inStock()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * الحصول على المنتجات في العرض
     */
    public function getOnSaleProducts($limit = 8)
    {
        return Cache::remember("on_sale_products_{$limit}", 900, function () use ($limit) {
            return Product::onSale()
                ->active()
                ->inStock()
                ->orderByRaw('((price - sale_price) / price) DESC')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * الحصول على توصيات بناءً على الفئة
     */
    public function getCategoryRecommendations($categoryId, $limit = 8)
    {
        return Cache::remember("category_recommendations_{$categoryId}", 3600, function () use ($categoryId, $limit) {
            return Product::where('category_id', $categoryId)
                ->active()
                ->inStock()
                ->orderBy('rating_average', 'desc')
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * الحصول على توصيات بناءً على العلامة التجارية
     */
    public function getBrandRecommendations($brand, $limit = 8)
    {
        return Cache::remember("brand_recommendations_{$brand}", 3600, function () use ($brand, $limit) {
            return Product::where('brand', $brand)
                ->active()
                ->inStock()
                ->orderBy('rating_average', 'desc')
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * تحليل اتجاهات السوق
     */
    public function getMarketTrends()
    {
        return Cache::remember('market_trends', 7200, function () {
            return [
                'trending_categories' => $this->getTrendingCategories(),
                'trending_brands' => $this->getTrendingBrands(),
                'price_trends' => $this->getPriceTrends(),
                'seasonal_products' => $this->getSeasonalProducts(),
            ];
        });
    }

    /**
     * الحصول على الفئات الرائجة
     */
    private function getTrendingCategories()
    {
        return DB::table('categories')
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->join('favorites', 'products.id', '=', 'favorites.product_id')
            ->select('categories.name_ar', DB::raw('COUNT(favorites.id) as favorite_count'))
            ->where('favorites.created_at', '>=', now()->subDays(30))
            ->groupBy('categories.id', 'categories.name_ar')
            ->orderBy('favorite_count', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * الحصول على العلامات التجارية الرائجة
     */
    private function getTrendingBrands()
    {
        return DB::table('products')
            ->join('favorites', 'products.id', '=', 'favorites.product_id')
            ->select('products.brand', DB::raw('COUNT(favorites.id) as favorite_count'))
            ->where('favorites.created_at', '>=', now()->subDays(30))
            ->whereNotNull('products.brand')
            ->groupBy('products.brand')
            ->orderBy('favorite_count', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * الحصول على اتجاهات الأسعار
     */
    private function getPriceTrends()
    {
        // تحليل متوسط الأسعار في الفئات المختلفة
        return DB::table('categories')
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->select('categories.name_ar', DB::raw('AVG(products.price) as avg_price'))
            ->groupBy('categories.id', 'categories.name_ar')
            ->orderBy('avg_price', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * الحصول على المنتجات الموسمية
     */
    private function getSeasonalProducts()
    {
        $currentMonth = now()->month;
        
        // تحديد المنتجات المناسبة للموسم الحالي
        $seasonalCategories = [];
        
        if (in_array($currentMonth, [12, 1, 2])) {
            // الشتاء
            $seasonalCategories = ['تبريد', 'دفايات', 'زجاج'];
        } elseif (in_array($currentMonth, [3, 4, 5])) {
            // الربيع
            $seasonalCategories = ['إطارات', 'فرامل', 'كهرباء'];
        } elseif (in_array($currentMonth, [6, 7, 8])) {
            // الصيف
            $seasonalCategories = ['تكييف', 'تبريد', 'بطاريات'];
        } else {
            // الخريف
            $seasonalCategories = ['إطارات', 'فرامل', 'محرك'];
        }

        return Product::whereHas('category', function($query) use ($seasonalCategories) {
            $query->whereIn('name_ar', $seasonalCategories);
        })
        ->active()
        ->inStock()
        ->orderBy('rating_average', 'desc')
        ->limit(8)
        ->get();
    }
} 