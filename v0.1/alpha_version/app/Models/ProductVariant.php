<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'name_en',
        'description',
        'description_en',
        'price',
        'compare_price',
        'cost_price',
        'wholesale_price',
        'tax_rate',
        'weight',
        'weight_unit',
        'dimensions',
        'volume',
        'volume_unit',
        'stock_quantity',
        'reserved_quantity',
        'available_quantity',
        'min_order_quantity',
        'max_order_quantity',
        'is_active',
        'is_featured',
        'is_bestseller',
        'is_on_sale',
        'sale_price',
        'sale_start_date',
        'sale_end_date',
        'primary_image_id',
        'barcode',
        'ean',
        'upc',
        'isbn',
        'mpn',
        'gtin',
        'attributes',
        'options',
        'metadata',
    ];

    protected $hidden = [
        'cost_price',
        'wholesale_price',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'weight' => 'decimal:3',
        'volume' => 'decimal:3',
        'stock_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'min_order_quantity' => 'integer',
        'max_order_quantity' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_on_sale' => 'boolean',
        'sale_price' => 'decimal:2',
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
        'dimensions' => 'array',
        'attributes' => 'array',
        'options' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function primaryImage()
    {
        return $this->belongsTo(ProductImage::class, 'primary_image_id');
    }

    public function images()
    {
        return $this->belongsToMany(ProductImage::class, 'product_variant_images');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    // Scopes
    public function scopeByProduct(Builder $query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeBestseller(Builder $query)
    {
        return $query->where('is_bestseller', true);
    }

    public function scopeOnSale(Builder $query)
    {
        return $query->where('is_on_sale', true);
    }

    public function scopeInStock(Builder $query)
    {
        return $query->where('available_quantity', '>', 0);
    }

    public function scopeOutOfStock(Builder $query)
    {
        return $query->where('available_quantity', '<=', 0);
    }

    public function scopeLowStock(Builder $query, $threshold = 10)
    {
        return $query->where('available_quantity', '>', 0)
            ->where('available_quantity', '<=', $threshold);
    }

    public function scopeBySku(Builder $query, $sku)
    {
        return $query->where('sku', $sku);
    }

    public function scopeByBarcode(Builder $query, $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    public function scopeByPriceRange(Builder $query, $minPrice = null, $maxPrice = null)
    {
        if ($minPrice) {
            $query->where('price', '>=', $minPrice);
        }
        
        if ($maxPrice) {
            $query->where('price', '<=', $maxPrice);
        }
        
        return $query;
    }

    public function scopeByWeightRange(Builder $query, $minWeight = null, $maxWeight = null)
    {
        if ($minWeight) {
            $query->where('weight', '>=', $minWeight);
        }
        
        if ($maxWeight) {
            $query->where('weight', '<=', $maxWeight);
        }
        
        return $query;
    }

    public function scopeByAttribute(Builder $query, $attribute, $value)
    {
        return $query->whereJsonContains("attributes->{$attribute}", $value);
    }

    public function scopeByOption(Builder $query, $option, $value)
    {
        return $query->whereJsonContains("options->{$option}", $value);
    }

    public function scopeWithPrimaryImage(Builder $query)
    {
        return $query->whereNotNull('primary_image_id');
    }

    public function scopeWithoutPrimaryImage(Builder $query)
    {
        return $query->whereNull('primary_image_id');
    }

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->whereHas('orderItems');
    }

    public function scopeMostOrdered(Builder $query, $limit = 10)
    {
        return $query->withCount('orderItems')
            ->orderBy('order_items_count', 'desc')
            ->limit($limit);
    }

    public function scopeTopRated(Builder $query, $limit = 10)
    {
        return $query->withAvg('reviews', 'rating')
            ->orderBy('reviews_avg_rating', 'desc')
            ->limit($limit);
    }

    // Helper Methods
    public function getNameAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->name) {
            return $this->name;
        }
        
        return $value ?: $this->name_en;
    }

    public function getDescriptionAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->description) {
            return $this->description;
        }
        
        return $value ?: $this->description_en;
    }

    public function getNameArAttribute()
    {
        return $this->name;
    }

    public function getDescriptionArAttribute()
    {
        return $this->description;
    }

    public function getNameEnAttribute()
    {
        return $this->name_en;
    }

    public function getDescriptionEnAttribute()
    {
        return $this->description_en;
    }

    public function getCurrentPriceAttribute()
    {
        if ($this->isOnSale() && $this->sale_price) {
            return $this->sale_price;
        }
        
        return $this->price;
    }

    public function getDiscountPercentageAttribute()
    {
        if (!$this->compare_price || $this->compare_price <= $this->getCurrentPriceAttribute()) {
            return 0;
        }
        
        return round((($this->compare_price - $this->getCurrentPriceAttribute()) / $this->compare_price) * 100, 2);
    }

    public function getDiscountAmountAttribute()
    {
        if (!$this->compare_price || $this->compare_price <= $this->getCurrentPriceAttribute()) {
            return 0;
        }
        
        return $this->compare_price - $this->getCurrentPriceAttribute();
    }

    public function getProfitMarginAttribute()
    {
        if (!$this->cost_price || $this->cost_price <= 0) {
            return 0;
        }
        
        return round((($this->getCurrentPriceAttribute() - $this->cost_price) / $this->getCurrentPriceAttribute()) * 100, 2);
    }

    public function getProfitAmountAttribute()
    {
        if (!$this->cost_price || $this->cost_price <= 0) {
            return 0;
        }
        
        return $this->getCurrentPriceAttribute() - $this->cost_price;
    }

    public function getPriceWithTaxAttribute()
    {
        $price = $this->getCurrentPriceAttribute();
        $taxRate = $this->tax_rate ?: $this->product->tax_rate ?: 15; // Default 15% VAT
        
        return $price + ($price * $taxRate / 100);
    }

    public function getTaxAmountAttribute()
    {
        $price = $this->getCurrentPriceAttribute();
        $taxRate = $this->tax_rate ?: $this->product->tax_rate ?: 15; // Default 15% VAT
        
        return $price * $taxRate / 100;
    }

    public function getCurrentPriceFormattedAttribute()
    {
        return number_format($this->getCurrentPriceAttribute(), 2) . ' SAR';
    }

    public function getCurrentPriceFormattedArAttribute()
    {
        return number_format($this->getCurrentPriceAttribute(), 2) . ' ريال';
    }

    public function getComparePriceFormattedAttribute()
    {
        return $this->compare_price ? number_format($this->compare_price, 2) . ' SAR' : null;
    }

    public function getComparePriceFormattedArAttribute()
    {
        return $this->compare_price ? number_format($this->compare_price, 2) . ' ريال' : null;
    }

    public function getSalePriceFormattedAttribute()
    {
        return $this->sale_price ? number_format($this->sale_price, 2) . ' SAR' : null;
    }

    public function getSalePriceFormattedArAttribute()
    {
        return $this->sale_price ? number_format($this->sale_price, 2) . ' ريال' : null;
    }

    public function getPriceWithTaxFormattedAttribute()
    {
        return number_format($this->getPriceWithTaxAttribute(), 2) . ' SAR';
    }

    public function getPriceWithTaxFormattedArAttribute()
    {
        return number_format($this->getPriceWithTaxAttribute(), 2) . ' ريال';
    }

    public function getTaxAmountFormattedAttribute()
    {
        return number_format($this->getTaxAmountAttribute(), 2) . ' SAR';
    }

    public function getTaxAmountFormattedArAttribute()
    {
        return number_format($this->getTaxAmountAttribute(), 2) . ' ريال';
    }

    public function getWeightFormattedAttribute()
    {
        if (!$this->weight) {
            return null;
        }
        
        return number_format($this->weight, 2) . ' ' . ($this->weight_unit ?: 'kg');
    }

    public function getWeightFormattedArAttribute()
    {
        return $this->getWeightFormattedAttribute();
    }

    public function getVolumeFormattedAttribute()
    {
        if (!$this->volume) {
            return null;
        }
        
        return number_format($this->volume, 2) . ' ' . ($this->volume_unit ?: 'L');
    }

    public function getVolumeFormattedArAttribute()
    {
        return $this->getVolumeFormattedAttribute();
    }

    public function getDimensionsFormattedAttribute()
    {
        if (!$this->dimensions || !isset($this->dimensions['length']) || !isset($this->dimensions['width']) || !isset($this->dimensions['height'])) {
            return null;
        }
        
        return $this->dimensions['length'] . ' × ' . $this->dimensions['width'] . ' × ' . $this->dimensions['height'] . ' cm';
    }

    public function getDimensionsFormattedArAttribute()
    {
        return $this->getDimensionsFormattedAttribute();
    }

    public function getStockLevelAttribute()
    {
        if ($this->available_quantity <= 0) {
            return 'out_of_stock';
        }
        
        if ($this->available_quantity <= 10) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }

    public function getStockLevelPercentageAttribute()
    {
        if (!$this->stock_quantity) {
            return 0;
        }
        
        return round(($this->available_quantity / $this->stock_quantity) * 100, 2);
    }

    public function getStockLevelBadgeAttribute()
    {
        switch ($this->getStockLevelAttribute()) {
            case 'in_stock':
                return '<span class="badge bg-success">In Stock</span>';
            case 'low_stock':
                return '<span class="badge bg-warning">Low Stock</span>';
            case 'out_of_stock':
                return '<span class="badge bg-danger">Out of Stock</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    public function getStockLevelBadgeArAttribute()
    {
        switch ($this->getStockLevelAttribute()) {
            case 'in_stock':
                return '<span class="badge bg-success">متوفر</span>';
            case 'low_stock':
                return '<span class="badge bg-warning">مخزون منخفض</span>';
            case 'out_of_stock':
                return '<span class="badge bg-danger">غير متوفر</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getOnSaleBadgeAttribute()
    {
        return $this->isOnSale() 
            ? '<span class="badge bg-danger">On Sale</span>'
            : '<span class="badge bg-secondary">Regular Price</span>';
    }

    public function getOnSaleBadgeArAttribute()
    {
        return $this->isOnSale() 
            ? '<span class="badge bg-danger">عرض خاص</span>'
            : '<span class="badge bg-secondary">سعر عادي</span>';
    }

    public function getActiveBadgeAttribute()
    {
        return $this->is_active 
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-danger">Inactive</span>';
    }

    public function getActiveBadgeArAttribute()
    {
        return $this->is_active 
            ? '<span class="badge bg-success">نشط</span>'
            : '<span class="badge bg-danger">غير نشط</span>';
    }

    public function getFeaturedBadgeAttribute()
    {
        return $this->is_featured 
            ? '<span class="badge bg-primary">Featured</span>'
            : '<span class="badge bg-secondary">Regular</span>';
    }

    public function getFeaturedBadgeArAttribute()
    {
        return $this->is_featured 
            ? '<span class="badge bg-primary">مميز</span>'
            : '<span class="badge bg-secondary">عادي</span>';
    }

    public function getBestsellerBadgeAttribute()
    {
        return $this->is_bestseller 
            ? '<span class="badge bg-warning">Bestseller</span>'
            : '<span class="badge bg-secondary">Regular</span>';
    }

    public function getBestsellerBadgeArAttribute()
    {
        return $this->is_bestseller 
            ? '<span class="badge bg-warning">الأكثر مبيعاً</span>'
            : '<span class="badge bg-secondary">عادي</span>';
    }

    public function getPrimaryImageUrlAttribute()
    {
        if ($this->primaryImage) {
            return $this->primaryImage->getImageUrlAttribute();
        }
        
        return $this->product->getPrimaryImageUrlAttribute();
    }

    public function getPrimaryImageThumbnailUrlAttribute()
    {
        if ($this->primaryImage) {
            return $this->primaryImage->getThumbnailUrlAttribute();
        }
        
        return $this->product->getPrimaryImageThumbnailUrlAttribute();
    }

    public function getAttributesListAttribute()
    {
        if (!$this->attributes) {
            return [];
        }
        
        $list = [];
        foreach ($this->attributes as $key => $value) {
            $list[] = ucfirst($key) . ': ' . $value;
        }
        
        return $list;
    }

    public function getOptionsListAttribute()
    {
        if (!$this->options) {
            return [];
        }
        
        $list = [];
        foreach ($this->options as $key => $value) {
            $list[] = ucfirst($key) . ': ' . $value;
        }
        
        return $list;
    }

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->format('M d, Y H:i');
    }

    public function getCreatedAtFormattedArAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    public function getUpdatedAtFormattedAttribute()
    {
        return $this->updated_at->format('M d, Y H:i');
    }

    public function getUpdatedAtFormattedArAttribute()
    {
        return $this->updated_at->format('d M Y H:i');
    }

    public function getAgeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getAgeArAttribute()
    {
        $diff = $this->created_at->diff(Carbon::now());
        
        if ($diff->days > 0) {
            return $diff->days . ' يوم';
        } elseif ($diff->h > 0) {
            return $diff->h . ' ساعة';
        } elseif ($diff->i > 0) {
            return $diff->i . ' دقيقة';
        } else {
            return 'الآن';
        }
    }

    // Business Logic
    public function isActive()
    {
        return $this->is_active;
    }

    public function isFeatured()
    {
        return $this->is_featured;
    }

    public function isBestseller()
    {
        return $this->is_bestseller;
    }

    public function isOnSale()
    {
        if (!$this->is_on_sale || !$this->sale_price) {
            return false;
        }
        
        $now = Carbon::now();
        
        if ($this->sale_start_date && $now < $this->sale_start_date) {
            return false;
        }
        
        if ($this->sale_end_date && $now > $this->sale_end_date) {
            return false;
        }
        
        return true;
    }

    public function isInStock()
    {
        return $this->available_quantity > 0;
    }

    public function isLowStock($threshold = 10)
    {
        return $this->available_quantity > 0 && $this->available_quantity <= $threshold;
    }

    public function isOutOfStock()
    {
        return $this->available_quantity <= 0;
    }

    public function canBeOrdered($quantity = 1)
    {
        if (!$this->isActive()) {
            return false;
        }
        
        if ($this->isOutOfStock()) {
            return false;
        }
        
        if ($quantity > $this->available_quantity) {
            return false;
        }
        
        if ($this->min_order_quantity && $quantity < $this->min_order_quantity) {
            return false;
        }
        
        if ($this->max_order_quantity && $quantity > $this->max_order_quantity) {
            return false;
        }
        
        return true;
    }

    public function hasDiscount()
    {
        return $this->getDiscountPercentageAttribute() > 0;
    }

    public function hasSale()
    {
        return $this->isOnSale();
    }

    public function hasPrimaryImage()
    {
        return $this->primary_image_id !== null;
    }

    public function hasAttributes()
    {
        return !empty($this->attributes);
    }

    public function hasOptions()
    {
        return !empty($this->options);
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
        $this->clearCache();
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
        $this->clearCache();
    }

    public function feature()
    {
        $this->update(['is_featured' => true]);
        $this->clearCache();
    }

    public function unfeature()
    {
        $this->update(['is_featured' => false]);
        $this->clearCache();
    }

    public function markAsBestseller()
    {
        $this->update(['is_bestseller' => true]);
        $this->clearCache();
    }

    public function unmarkAsBestseller()
    {
        $this->update(['is_bestseller' => false]);
        $this->clearCache();
    }

    public function startSale($salePrice, $startDate = null, $endDate = null)
    {
        $this->update([
            'is_on_sale' => true,
            'sale_price' => $salePrice,
            'sale_start_date' => $startDate,
            'sale_end_date' => $endDate,
        ]);
        $this->clearCache();
    }

    public function endSale()
    {
        $this->update([
            'is_on_sale' => false,
            'sale_price' => null,
            'sale_start_date' => null,
            'sale_end_date' => null,
        ]);
        $this->clearCache();
    }

    public function updatePrice($price, $comparePrice = null)
    {
        $this->update([
            'price' => $price,
            'compare_price' => $comparePrice,
        ]);
        $this->clearCache();
    }

    public function updateStock($quantity)
    {
        $this->update(['stock_quantity' => $quantity]);
        $this->updateAvailableQuantity();
        $this->clearCache();
    }

    public function reserveStock($quantity)
    {
        if ($this->available_quantity >= $quantity) {
            $this->update([
                'reserved_quantity' => $this->reserved_quantity + $quantity,
            ]);
            $this->updateAvailableQuantity();
            $this->clearCache();
            return true;
        }
        
        return false;
    }

    public function releaseStock($quantity)
    {
        $newReserved = max(0, $this->reserved_quantity - $quantity);
        $this->update(['reserved_quantity' => $newReserved]);
        $this->updateAvailableQuantity();
        $this->clearCache();
    }

    public function deductStock($quantity)
    {
        if ($this->available_quantity >= $quantity) {
            $this->update([
                'stock_quantity' => $this->stock_quantity - $quantity,
                'reserved_quantity' => max(0, $this->reserved_quantity - $quantity),
            ]);
            $this->updateAvailableQuantity();
            $this->clearCache();
            return true;
        }
        
        return false;
    }

    public function addStock($quantity)
    {
        $this->update(['stock_quantity' => $this->stock_quantity + $quantity]);
        $this->updateAvailableQuantity();
        $this->clearCache();
    }

    protected function updateAvailableQuantity()
    {
        $this->available_quantity = max(0, $this->stock_quantity - $this->reserved_quantity);
        $this->saveQuietly();
    }

    public function setPrimaryImage($imageId)
    {
        $this->update(['primary_image_id' => $imageId]);
        $this->clearCache();
    }

    public function removePrimaryImage()
    {
        $this->update(['primary_image_id' => null]);
        $this->clearCache();
    }

    public function addAttribute($key, $value)
    {
        $attributes = $this->attributes ?: [];
        $attributes[$key] = $value;
        $this->update(['attributes' => $attributes]);
        $this->clearCache();
    }

    public function removeAttribute($key)
    {
        $attributes = $this->attributes ?: [];
        unset($attributes[$key]);
        $this->update(['attributes' => $attributes]);
        $this->clearCache();
    }

    public function addOption($key, $value)
    {
        $options = $this->options ?: [];
        $options[$key] = $value;
        $this->update(['options' => $options]);
        $this->clearCache();
    }

    public function removeOption($key)
    {
        $options = $this->options ?: [];
        unset($options[$key]);
        $this->update(['options' => $options]);
        $this->clearCache();
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->sku = $this->sku . '_copy';
        $duplicate->is_active = false;
        $duplicate->is_featured = false;
        $duplicate->is_bestseller = false;
        $duplicate->is_on_sale = false;
        $duplicate->primary_image_id = null;
        $duplicate->save();
        
        return $duplicate;
    }

    public function getAverageRating()
    {
        return Cache::remember("variant_avg_rating_{$this->id}", 3600, function () {
            return $this->reviews()->avg('rating') ?: 0;
        });
    }

    public function getReviewsCount()
    {
        return Cache::remember("variant_reviews_count_{$this->id}", 3600, function () {
            return $this->reviews()->count();
        });
    }

    public function getTotalOrders()
    {
        return Cache::remember("variant_orders_count_{$this->id}", 3600, function () {
            return $this->orderItems()->count();
        });
    }

    public function getTotalRevenue()
    {
        return Cache::remember("variant_revenue_{$this->id}", 3600, function () {
            return $this->orderItems()->sum('total_price');
        });
    }

    public function getTotalProfit()
    {
        return Cache::remember("variant_profit_{$this->id}", 3600, function () {
            return $this->orderItems()->sum('total_price') - ($this->cost_price * $this->orderItems()->sum('quantity'));
        });
    }

    // Static Methods
    public static function getVariantsCountForProduct($productId)
    {
        return Cache::remember("product_variants_count_{$productId}", 3600, function () use ($productId) {
            return static::where('product_id', $productId)->count();
        });
    }

    public static function getActiveVariantsCountForProduct($productId)
    {
        return Cache::remember("product_active_variants_count_{$productId}", 3600, function () use ($productId) {
            return static::where('product_id', $productId)
                ->where('is_active', true)
                ->count();
        });
    }

    public static function getInStockVariantsCountForProduct($productId)
    {
        return Cache::remember("product_instock_variants_count_{$productId}", 3600, function () use ($productId) {
            return static::where('product_id', $productId)
                ->where('available_quantity', '>', 0)
                ->count();
        });
    }

    public static function getVariantsForProduct($productId, $activeOnly = true)
    {
        $query = static::where('product_id', $productId);
        
        if ($activeOnly) {
            $query->where('is_active', true);
        }
        
        return $query->orderBy('sort_order')->get();
    }

    public static function findBySku($sku)
    {
        return static::where('sku', $sku)->first();
    }

    public static function findByBarcode($barcode)
    {
        return static::where('barcode', $barcode)->first();
    }

    public static function getOnSaleVariants()
    {
        return static::where('is_on_sale', true)
            ->where('is_active', true)
            ->where('available_quantity', '>', 0)
            ->get();
    }

    public static function getFeaturedVariants()
    {
        return static::where('is_featured', true)
            ->where('is_active', true)
            ->where('available_quantity', '>', 0)
            ->get();
    }

    public static function getBestsellerVariants()
    {
        return static::where('is_bestseller', true)
            ->where('is_active', true)
            ->where('available_quantity', '>', 0)
            ->get();
    }

    public static function getLowStockVariants($threshold = 10)
    {
        return static::where('available_quantity', '>', 0)
            ->where('available_quantity', '<=', $threshold)
            ->where('is_active', true)
            ->get();
    }

    public static function getOutOfStockVariants()
    {
        return static::where('available_quantity', '<=', 0)
            ->where('is_active', true)
            ->get();
    }

    public static function getVariantsStats($productId = null)
    {
        $query = static::query();
        
        if ($productId) {
            $query->where('product_id', $productId);
        }

        return Cache::remember("variants_stats" . ($productId ? "_{$productId}" : ""), 3600, function () use ($query) {
            return [
                'total' => $query->count(),
                'active' => $query->where('is_active', true)->count(),
                'featured' => $query->where('is_featured', true)->count(),
                'bestseller' => $query->where('is_bestseller', true)->count(),
                'on_sale' => $query->where('is_on_sale', true)->count(),
                'in_stock' => $query->where('available_quantity', '>', 0)->count(),
                'out_of_stock' => $query->where('available_quantity', '<=', 0)->count(),
                'low_stock' => $query->where('available_quantity', '>', 0)->where('available_quantity', '<=', 10)->count(),
                'with_primary_image' => $query->whereNotNull('primary_image_id')->count(),
                'total_stock' => $query->sum('stock_quantity'),
                'total_available' => $query->sum('available_quantity'),
                'total_reserved' => $query->sum('reserved_quantity'),
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        $productId = $this->product_id;
        
        Cache::forget("product_variants_count_{$productId}");
        Cache::forget("product_active_variants_count_{$productId}");
        Cache::forget("product_instock_variants_count_{$productId}");
        Cache::forget("variants_stats_{$productId}");
        Cache::forget("variants_stats");
        
        Cache::forget("variant_avg_rating_{$this->id}");
        Cache::forget("variant_reviews_count_{$this->id}");
        Cache::forget("variant_orders_count_{$this->id}");
        Cache::forget("variant_revenue_{$this->id}");
        Cache::forget("variant_profit_{$this->id}");
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($variant) {
            if (!isset($variant->is_active)) {
                $variant->is_active = true;
            }
            
            if (!isset($variant->is_featured)) {
                $variant->is_featured = false;
            }
            
            if (!isset($variant->is_bestseller)) {
                $variant->is_bestseller = false;
            }
            
            if (!isset($variant->is_on_sale)) {
                $variant->is_on_sale = false;
            }
            
            if (!$variant->available_quantity) {
                $variant->available_quantity = $variant->stock_quantity - $variant->reserved_quantity;
            }
        });

        static::created(function ($variant) {
            $variant->clearCache();
        });

        static::updated(function ($variant) {
            $variant->clearCache();
        });

        static::deleted(function ($variant) {
            $variant->clearCache();
        });
    }
}