<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'slug',
        'description',
        'description_en',
        'short_description',
        'short_description_en',
        'sku',
        'barcode',
        'brand',
        'model',
        'year_from',
        'year_to',
        'category_id',
        'shop_id',
        'price',
        'sale_price',
        'cost_price',
        'weight',
        'dimensions',
        'stock_quantity',
        'min_stock_quantity',
        'max_stock_quantity',
        'is_active',
        'is_featured',
        'is_bestseller',
        'is_new',
        'is_on_sale',
        'sale_start_date',
        'sale_end_date',
        'images',
        'specifications',
        'compatibility',
        'warranty_period',
        'warranty_type',
        'return_policy',
        'shipping_info',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'view_count',
        'favorite_count',
        'rating_average',
        'rating_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'stock_quantity' => 'integer',
        'min_stock_quantity' => 'integer',
        'max_stock_quantity' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_new' => 'boolean',
        'is_on_sale' => 'boolean',
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
        'images' => 'array',
        'specifications' => 'array',
        'compatibility' => 'array',
        'view_count' => 'integer',
        'favorite_count' => 'integer',
        'rating_average' => 'decimal:1',
        'rating_count' => 'integer',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function relatedProducts()
    {
        return $this->category->products()
            ->where('id', '!=', $this->id)
            ->where('is_active', true)
            ->limit(8);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeBestseller($query)
    {
        return $query->where('is_bestseller', true);
    }

    public function scopeNew($query)
    {
        return $query->where('is_new', true);
    }

    public function scopeOnSale($query)
    {
        return $query->where('is_on_sale', true)
            ->where('sale_start_date', '<=', now())
            ->where('sale_end_date', '>=', now());
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('name_en', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('description_en', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('brand', 'like', "%{$search}%");
        });
    }

    // Helper Methods
    public function getFinalPrice(): float
    {
        if ($this->isOnSale() && $this->sale_price) {
            return $this->sale_price;
        }
        return $this->price;
    }

    public function getFormattedPrice(): string
    {
        return number_format($this->getFinalPrice(), 2) . ' ريال';
    }

    public function getFormattedSalePrice(): string
    {
        return number_format($this->sale_price, 2) . ' ريال';
    }

    public function getFormattedOriginalPrice(): string
    {
        return number_format($this->price, 2) . ' ريال';
    }

    /**
     * حساب نسبة الخصم
     */
    public function getDiscountPercentage(): int
    {
        if ($this->isOnSale() && $this->sale_price && $this->price > 0) {
            return round((($this->price - $this->sale_price) / $this->price) * 100);
        }
        return 0;
    }

    /**
     * التحقق من العرض
     */
    public function isOnSale(): bool
    {
        return $this->is_on_sale && 
               $this->sale_start_date <= now() && 
               $this->sale_end_date >= now();
    }

    /**
     * التحقق من المخزون
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * التحقق من المخزون المنخفض
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->min_stock_quantity;
    }

    /**
     * الحصول على الصورة الرئيسية مع تحسين الأداء
     */
    public function getMainImage(): string
    {
        return \App\Helpers\ImageHelper::getImageUrl($this->images);
    }

    /**
     * الحصول على جميع الصور
     */
    public function getImages(): array
    {
        return \App\Helpers\ImageHelper::getAllImagesUrls($this->images);
    }

    /**
     * الحصول على الصورة المصغرة
     */
    public function getThumbnailUrl(): string
    {
        return \App\Helpers\ImageHelper::getThumbnailUrl($this->images);
    }

    /**
     * التحقق من وجود الصور
     */
    public function hasImages(): bool
    {
        return \App\Helpers\ImageHelper::imageExists($this->images);
    }

    /**
     * الحصول على شارة الحالة
     */
    public function getStatusBadge(): string
    {
        if (!$this->is_active) {
            return '<span class="badge bg-danger">غير نشط</span>';
        }
        
        if (!$this->isInStock()) {
            return '<span class="badge bg-warning">نفذ المخزون</span>';
        }
        
        if ($this->isOnSale()) {
            return '<span class="badge bg-success">عرض خاص</span>';
        }
        
        if ($this->is_featured) {
            return '<span class="badge bg-primary">مميز</span>';
        }
        
        return '<span class="badge bg-success">متوفر</span>';
    }

    /**
     * الحصول على الاسم المحلي
     */
    public function getLocalizedName(): string
    {
        return app()->getLocale() === 'ar' ? $this->name : $this->name_en;
    }

    /**
     * الحصول على الوصف المحلي
     */
    public function getLocalizedDescription(): string
    {
        return app()->getLocale() === 'ar' ? $this->description : $this->description_en;
    }

    /**
     * الحصول على الوصف المختصر المحلي
     */
    public function getLocalizedShortDescription(): string
    {
        return app()->getLocale() === 'ar' ? $this->short_description : $this->short_description_en;
    }

    /**
     * زيادة عدد المشاهدات مع تحسين الأداء
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * تحديث إحصائيات التقييم
     */
    public function updateRatingStats(): void
    {
        $stats = $this->reviews()
            ->selectRaw('AVG(rating) as average, COUNT(*) as count')
            ->first();
        
        $this->update([
            'rating_average' => $stats->average ?? 0,
            'rating_count' => $stats->count ?? 0,
        ]);
    }

    /**
     * الحصول على نص التوافق
     */
    public function getCompatibilityText(): string
    {
        if (empty($this->compatibility)) {
            return 'غير محدد';
        }
        
        return implode(', ', $this->compatibility);
    }

    /**
     * الحصول على نص الضمان
     */
    public function getWarrantyText(): string
    {
        if (!$this->warranty_period) {
            return 'لا يوجد ضمان';
        }
        
        return $this->warranty_period . ' ' . $this->warranty_type;
    }

    /**
     * الحصول على المنتجات ذات الصلة المحسنة مع الكاش
     */
    public function getRelatedProducts($limit = 8)
    {
        $cacheKey = "related_products_{$this->id}_{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($limit) {
            return Product::where('category_id', $this->category_id)
                ->where('id', '!=', $this->id)
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->with(['category', 'shop'])
                ->orderBy('rating_average', 'desc')
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * الحصول على المنتجات المماثلة بناءً على العلامة التجارية مع الكاش
     */
    public function getSimilarProducts($limit = 6)
    {
        $cacheKey = "similar_products_{$this->id}_{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($limit) {
            return Product::where('brand', $this->brand)
                ->where('id', '!=', $this->id)
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->with(['category', 'shop'])
                ->orderBy('rating_average', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * حساب متوسط التقييم
     */
    public function getAverageRating(): float
    {
        return $this->rating_average ?? 0;
    }

    /**
     * الحصول على عدد التقييمات
     */
    public function getReviewsCount(): int
    {
        return $this->rating_count ?? 0;
    }

    /**
     * التحقق من كون المنتج جديد
     */
    public function isNew(): bool
    {
        return $this->created_at->diffInDays(now()) <= 30;
    }

    /**
     * الحصول على عمر المنتج
     */
    public function getAge(): string
    {
        $days = $this->created_at->diffInDays(now());
        
        if ($days == 0) {
            return 'اليوم';
        } elseif ($days == 1) {
            return 'أمس';
        } elseif ($days < 7) {
            return "منذ {$days} أيام";
        } elseif ($days < 30) {
            $weeks = floor($days / 7);
            return "منذ {$weeks} أسابيع";
        } else {
            $months = floor($days / 30);
            return "منذ {$months} أشهر";
        }
    }

    /**
     * الحصول على معلومات الشحن
     */
    public function getShippingInfo(): array
    {
        return [
            'weight' => $this->weight ?? 0,
            'dimensions' => $this->dimensions ?? [],
            'shipping_cost' => $this->calculateShippingCost(),
            'delivery_time' => $this->getDeliveryTime(),
        ];
    }

    /**
     * حساب تكلفة الشحن
     */
    private function calculateShippingCost(): float
    {
        // منطق حساب تكلفة الشحن
        $baseCost = 15.0; // تكلفة أساسية
        $weightCost = ($this->weight ?? 0) * 2; // 2 ريال لكل كيلو
        
        return $baseCost + $weightCost;
    }

    /**
     * الحصول على وقت التوصيل
     */
    private function getDeliveryTime(): string
    {
        $weight = $this->weight ?? 0;
        
        if ($weight < 5) {
            return '1-2 أيام عمل';
        } elseif ($weight < 15) {
            return '2-3 أيام عمل';
        } else {
            return '3-5 أيام عمل';
        }
    }
} 