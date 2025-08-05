<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

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
        'category_id',
        'brand_id',
        'supplier_id',
        'price',
        'sale_price',
        'cost_price',
        'wholesale_price',
        'tax_rate',
        'weight',
        'dimensions',
        'stock_quantity',
        'min_stock_quantity',
        'max_stock_quantity',
        'reserved_quantity',
        'available_quantity',
        'is_active',
        'is_featured',
        'is_bestseller',
        'is_new',
        'is_on_sale',
        'sale_start_date',
        'sale_end_date',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'images',
        'videos',
        'documents',
        'specifications',
        'warranty_info',
        'shipping_info',
        'tags',
        'related_products',
        'cross_sell_products',
        'up_sell_products',
        'rating',
        'rating_count',
        'view_count',
        'order_count',
        'favorite_count',
        'status',
        'published_at',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions' => 'array',
        'stock_quantity' => 'integer',
        'min_stock_quantity' => 'integer',
        'max_stock_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_new' => 'boolean',
        'is_on_sale' => 'boolean',
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
        'images' => 'array',
        'videos' => 'array',
        'documents' => 'array',
        'specifications' => 'array',
        'warranty_info' => 'array',
        'shipping_info' => 'array',
        'tags' => 'array',
        'related_products' => 'array',
        'cross_sell_products' => 'array',
        'up_sell_products' => 'array',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
        'view_count' => 'integer',
        'order_count' => 'integer',
        'favorite_count' => 'integer',
        'published_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function productImages()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeBestseller(Builder $query): Builder
    {
        return $query->where('is_bestseller', true);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('is_new', true);
    }

    public function scopeOnSale(Builder $query): Builder
    {
        return $query->where('is_on_sale', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('available_quantity', '>', 0);
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('available_quantity', '<=', 0);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereRaw('available_quantity <= min_stock_quantity');
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByBrand(Builder $query, int $brandId): Builder
    {
        return $query->where('brand_id', $brandId);
    }

    public function scopeByPriceRange(Builder $query, float $minPrice, float $maxPrice): Builder
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    public function scopeByRating(Builder $query, float $minRating): Builder
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeMostViewed(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('view_count', 'desc')->limit($limit);
    }

    public function scopeMostOrdered(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('order_count', 'desc')->limit($limit);
    }

    public function scopeMostFavorited(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('favorite_count', 'desc')->limit($limit);
    }

    public function scopeTopRated(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('rating', 'desc')->limit($limit);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('name_en', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('description_en', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%");
        });
    }

    // Helper Methods
    public function getLocalizedName(): string
    {
        return app()->getLocale() === 'ar' ? $this->name : $this->name_en;
    }

    public function getLocalizedDescription(): string
    {
        return app()->getLocale() === 'ar' ? $this->description : $this->description_en;
    }

    public function getLocalizedShortDescription(): string
    {
        return app()->getLocale() === 'ar' ? $this->short_description : $this->short_description_en;
    }

    public function getCurrentPrice(): float
    {
        if ($this->isOnSale() && $this->sale_price > 0) {
            return $this->sale_price;
        }
        
        return $this->price;
    }

    public function getCurrentPriceFormatted(): string
    {
        return number_format($this->getCurrentPrice(), 2) . ' ريال';
    }

    public function getOriginalPriceFormatted(): string
    {
        return number_format($this->price, 2) . ' ريال';
    }

    public function getSalePriceFormatted(): string
    {
        if (!$this->sale_price) {
            return '';
        }
        return number_format($this->sale_price, 2) . ' ريال';
    }

    public function getDiscountPercentage(): float
    {
        if (!$this->isOnSale() || !$this->sale_price) {
            return 0;
        }
        
        return round((($this->price - $this->sale_price) / $this->price) * 100, 2);
    }

    public function getDiscountAmount(): float
    {
        if (!$this->isOnSale() || !$this->sale_price) {
            return 0;
        }
        
        return $this->price - $this->sale_price;
    }

    public function getDiscountAmountFormatted(): string
    {
        return number_format($this->getDiscountAmount(), 2) . ' ريال';
    }

    public function getProfitMargin(): float
    {
        if (!$this->cost_price) {
            return 0;
        }
        
        $sellingPrice = $this->getCurrentPrice();
        return round((($sellingPrice - $this->cost_price) / $sellingPrice) * 100, 2);
    }

    public function getProfitAmount(): float
    {
        if (!$this->cost_price) {
            return 0;
        }
        
        return $this->getCurrentPrice() - $this->cost_price;
    }

    public function getProfitAmountFormatted(): string
    {
        return number_format($this->getProfitAmount(), 2) . ' ريال';
    }

    public function getStatusBadge(): string
    {
        $badges = [
            'draft' => '<span class="badge bg-secondary">مسودة</span>',
            'published' => '<span class="badge bg-success">منشور</span>',
            'archived' => '<span class="badge bg-warning">مؤرشف</span>',
            'discontinued' => '<span class="badge bg-danger">متوقف</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge bg-secondary">' . $this->status . '</span>';
    }

    public function getStockStatusBadge(): string
    {
        if ($this->available_quantity <= 0) {
            return '<span class="badge bg-danger">نفذ المخزون</span>';
        }
        
        if ($this->isLowStock()) {
            return '<span class="badge bg-warning">مخزون منخفض</span>';
        }
        
        return '<span class="badge bg-success">متوفر</span>';
    }

    public function getSaleBadge(): string
    {
        if (!$this->isOnSale()) {
            return '';
        }
        
        $discount = $this->getDiscountPercentage();
        return '<span class="badge bg-danger">خصم ' . $discount . '%</span>';
    }

    public function getRatingStars(): string
    {
        if (!$this->rating) {
            return '<span class="text-muted">لا توجد تقييمات</span>';
        }
        
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $stars .= '<i class="fas fa-star text-warning"></i>';
            } else {
                $stars .= '<i class="far fa-star text-muted"></i>';
            }
        }
        
        return $stars . ' <span class="ms-1">(' . number_format($this->rating, 1) . '/5)</span>';
    }

    public function getMainImage(): string
    {
        $images = $this->images ?? [];
        return $images[0] ?? '/images/placeholder-product.jpg';
    }

    public function getImages(): array
    {
        return $this->images ?? [];
    }

    public function getVideos(): array
    {
        return $this->videos ?? [];
    }

    public function getDocuments(): array
    {
        return $this->documents ?? [];
    }

    public function getSpecifications(): array
    {
        return $this->specifications ?? [];
    }

    public function getWarrantyInfo(): array
    {
        return $this->warranty_info ?? [];
    }

    public function getShippingInfo(): array
    {
        return $this->shipping_info ?? [];
    }

    public function getTags(): array
    {
        return $this->tags ?? [];
    }

    public function getRelatedProducts(): array
    {
        return $this->related_products ?? [];
    }

    public function getCrossSellProducts(): array
    {
        return $this->cross_sell_products ?? [];
    }

    public function getUpSellProducts(): array
    {
        return $this->up_sell_products ?? [];
    }

    public function getWeightFormatted(): string
    {
        if (!$this->weight) {
            return 'غير محدد';
        }
        
        return number_format($this->weight, 3) . ' كجم';
    }

    public function getDimensionsFormatted(): string
    {
        $dimensions = $this->dimensions ?? [];
        
        if (empty($dimensions)) {
            return 'غير محدد';
        }
        
        $length = $dimensions['length'] ?? 0;
        $width = $dimensions['width'] ?? 0;
        $height = $dimensions['height'] ?? 0;
        
        return $length . ' × ' . $width . ' × ' . $height . ' سم';
    }

    public function getTaxAmount(): float
    {
        return ($this->getCurrentPrice() * $this->tax_rate) / 100;
    }

    public function getTaxAmountFormatted(): string
    {
        return number_format($this->getTaxAmount(), 2) . ' ريال';
    }

    public function getPriceWithTax(): float
    {
        return $this->getCurrentPrice() + $this->getTaxAmount();
    }

    public function getPriceWithTaxFormatted(): string
    {
        return number_format($this->getPriceWithTax(), 2) . ' ريال';
    }

    // Business Logic Methods
    public function isOnSale(): bool
    {
        if (!$this->is_on_sale || !$this->sale_price) {
            return false;
        }
        
        $now = now();
        
        if ($this->sale_start_date && $now < $this->sale_start_date) {
            return false;
        }
        
        if ($this->sale_end_date && $now > $this->sale_end_date) {
            return false;
        }
        
        return true;
    }

    public function isInStock(): bool
    {
        return $this->available_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->available_quantity <= $this->min_stock_quantity;
    }

    public function isOutOfStock(): bool
    {
        return $this->available_quantity <= 0;
    }

    public function canBeOrdered(): bool
    {
        return $this->is_active && $this->isInStock() && $this->status === 'published';
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementOrderCount(): void
    {
        $this->increment('order_count');
    }

    public function incrementFavoriteCount(): void
    {
        $this->increment('favorite_count');
    }

    public function decrementFavoriteCount(): void
    {
        $this->decrement('favorite_count');
    }

    public function updateRating(): void
    {
        $reviews = $this->reviews()->whereNotNull('rating');
        
        if ($reviews->count() > 0) {
            $averageRating = $reviews->avg('rating');
            $this->update([
                'rating' => round($averageRating, 2),
                'rating_count' => $reviews->count(),
            ]);
        }
    }

    public function reserveStock(int $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }
        
        $this->increment('reserved_quantity', $quantity);
        $this->decrement('available_quantity', $quantity);
        
        return true;
    }

    public function releaseStock(int $quantity): void
    {
        $this->decrement('reserved_quantity', $quantity);
        $this->increment('available_quantity', $quantity);
    }

    public function deductStock(int $quantity): void
    {
        $this->decrement('stock_quantity', $quantity);
        $this->decrement('reserved_quantity', $quantity);
    }

    public function addStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);
        $this->increment('available_quantity', $quantity);
    }

    public function getStockLevel(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        }
        
        if ($this->isLowStock()) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }

    public function getStockLevelPercentage(): float
    {
        if ($this->max_stock_quantity == 0) {
            return 0;
        }
        
        return round(($this->available_quantity / $this->max_stock_quantity) * 100, 2);
    }

    public function getStockLevelBadge(): string
    {
        $level = $this->getStockLevel();
        
        $badges = [
            'out_of_stock' => '<span class="badge bg-danger">نفذ المخزون</span>',
            'low_stock' => '<span class="badge bg-warning">مخزون منخفض</span>',
            'in_stock' => '<span class="badge bg-success">متوفر</span>',
        ];
        
        return $badges[$level] ?? $badges['in_stock'];
    }

    public function getAverageOrderValue(): float
    {
        if ($this->order_count == 0) {
            return 0;
        }
        
        return $this->getCurrentPrice();
    }

    public function getTotalRevenue(): float
    {
        return $this->order_count * $this->getCurrentPrice();
    }

    public function getTotalRevenueFormatted(): string
    {
        return number_format($this->getTotalRevenue(), 2) . ' ريال';
    }

    public function getTotalProfit(): float
    {
        if (!$this->cost_price) {
            return 0;
        }
        
        return $this->order_count * $this->getProfitAmount();
    }

    public function getTotalProfitFormatted(): string
    {
        return number_format($this->getTotalProfit(), 2) . ' ريال';
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($product) {
            // Set default values
            if (is_null($product->is_active)) {
                $product->is_active = true;
            }
            
            if (is_null($product->is_featured)) {
                $product->is_featured = false;
            }
            
            if (is_null($product->is_bestseller)) {
                $product->is_bestseller = false;
            }
            
            if (is_null($product->is_new)) {
                $product->is_new = false;
            }
            
            if (is_null($product->is_on_sale)) {
                $product->is_on_sale = false;
            }
            
            if (is_null($product->stock_quantity)) {
                $product->stock_quantity = 0;
            }
            
            if (is_null($product->available_quantity)) {
                $product->available_quantity = $product->stock_quantity;
            }
            
            if (is_null($product->reserved_quantity)) {
                $product->reserved_quantity = 0;
            }
            
            if (is_null($product->min_stock_quantity)) {
                $product->min_stock_quantity = 5;
            }
            
            if (is_null($product->max_stock_quantity)) {
                $product->max_stock_quantity = 1000;
            }
            
            if (is_null($product->rating)) {
                $product->rating = 0;
            }
            
            if (is_null($product->rating_count)) {
                $product->rating_count = 0;
            }
            
            if (is_null($product->view_count)) {
                $product->view_count = 0;
            }
            
            if (is_null($product->order_count)) {
                $product->order_count = 0;
            }
            
            if (is_null($product->favorite_count)) {
                $product->favorite_count = 0;
            }
            
            if (is_null($product->status)) {
                $product->status = 'draft';
            }
            
            if (is_null($product->tax_rate)) {
                $product->tax_rate = 15.00; // Default 15% VAT
            }
        });

        static::created(function ($product) {
            // Clear cache
            Cache::forget('products_count');
            Cache::forget('active_products_count');
            Cache::forget('featured_products');
            Cache::forget('bestseller_products');
            Cache::forget('new_products');
        });

        static::updated(function ($product) {
            // Clear cache
            Cache::forget('products_count');
            Cache::forget('active_products_count');
            Cache::forget('featured_products');
            Cache::forget('bestseller_products');
            Cache::forget('new_products');
        });

        static::deleted(function ($product) {
            // Clear cache
            Cache::forget('products_count');
            Cache::forget('active_products_count');
            Cache::forget('featured_products');
            Cache::forget('bestseller_products');
            Cache::forget('new_products');
        });
    }
} 