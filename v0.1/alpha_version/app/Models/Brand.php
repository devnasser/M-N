<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Brand extends Model
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
        'logo',
        'banner_image',
        'website_url',
        'email',
        'phone',
        'address',
        'country',
        'city',
        'postal_code',
        'latitude',
        'longitude',
        'is_active',
        'is_featured',
        'is_verified',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'products_count',
        'view_count',
        'rating',
        'rating_count',
        'founded_year',
        'headquarters',
        'parent_company',
        'ceo',
        'employees_count',
        'annual_revenue',
        'social_media',
        'certifications',
        'awards',
        'warranty_info',
        'support_info',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'sort_order' => 'integer',
        'products_count' => 'integer',
        'view_count' => 'integer',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
        'founded_year' => 'integer',
        'employees_count' => 'integer',
        'annual_revenue' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'social_media' => 'array',
        'certifications' => 'array',
        'awards' => 'array',
        'warranty_info' => 'array',
        'support_info' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'brand_category');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function parentCompany()
    {
        return $this->belongsTo(Brand::class, 'parent_company');
    }

    public function subsidiaries()
    {
        return $this->hasMany(Brand::class, 'parent_company');
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

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeByCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', $country);
    }

    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('products_count', 'desc')->limit($limit);
    }

    public function scopeMostViewed(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('view_count', 'desc')->limit($limit);
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
              ->orWhere('short_description', 'like', "%{$search}%")
              ->orWhere('short_description_en', 'like', "%{$search}%");
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

    public function getStatusBadge(): string
    {
        if (!$this->is_active) {
            return '<span class="badge bg-danger">غير نشط</span>';
        }
        
        if ($this->is_featured) {
            return '<span class="badge bg-primary">مميز</span>';
        }
        
        return '<span class="badge bg-success">نشط</span>';
    }

    public function getVerificationBadge(): string
    {
        if ($this->is_verified) {
            return '<span class="badge bg-success">موثق</span>';
        }
        
        return '<span class="badge bg-warning">غير موثق</span>';
    }

    public function getLogoUrl(): string
    {
        if (!$this->logo) {
            return '/images/placeholder-brand-logo.jpg';
        }
        
        return $this->logo;
    }

    public function getBannerImageUrl(): string
    {
        if (!$this->banner_image) {
            return '/images/placeholder-brand-banner.jpg';
        }
        
        return $this->banner_image;
    }

    public function getProductsCountFormatted(): string
    {
        if ($this->products_count == 0) {
            return 'لا توجد منتجات';
        }
        
        if ($this->products_count == 1) {
            return 'منتج واحد';
        }
        
        if ($this->products_count == 2) {
            return 'منتجان';
        }
        
        if ($this->products_count >= 3 && $this->products_count <= 10) {
            return $this->products_count . ' منتجات';
        }
        
        return $this->products_count . ' منتج';
    }

    public function getViewCountFormatted(): string
    {
        if ($this->view_count == 0) {
            return 'لا توجد مشاهدات';
        }
        
        if ($this->view_count < 1000) {
            return $this->view_count . ' مشاهدة';
        }
        
        if ($this->view_count < 1000000) {
            return number_format($this->view_count / 1000, 1) . ' ألف مشاهدة';
        }
        
        return number_format($this->view_count / 1000000, 1) . ' مليون مشاهدة';
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

    public function getFoundedYearFormatted(): string
    {
        if (!$this->founded_year) {
            return 'غير محدد';
        }
        
        return $this->founded_year;
    }

    public function getAge(): int
    {
        if (!$this->founded_year) {
            return 0;
        }
        
        return date('Y') - $this->founded_year;
    }

    public function getAgeFormatted(): string
    {
        $age = $this->getAge();
        
        if ($age == 0) {
            return 'غير محدد';
        }
        
        if ($age == 1) {
            return 'سنة واحدة';
        }
        
        if ($age == 2) {
            return 'سنتان';
        }
        
        if ($age >= 3 && $age <= 10) {
            return $age . ' سنوات';
        }
        
        return $age . ' سنة';
    }

    public function getEmployeesCountFormatted(): string
    {
        if (!$this->employees_count) {
            return 'غير محدد';
        }
        
        if ($this->employees_count < 1000) {
            return $this->employees_count . ' موظف';
        }
        
        if ($this->employees_count < 1000000) {
            return number_format($this->employees_count / 1000, 1) . ' ألف موظف';
        }
        
        return number_format($this->employees_count / 1000000, 1) . ' مليون موظف';
    }

    public function getAnnualRevenueFormatted(): string
    {
        if (!$this->annual_revenue) {
            return 'غير محدد';
        }
        
        if ($this->annual_revenue < 1000000) {
            return number_format($this->annual_revenue / 1000, 1) . ' ألف ريال';
        }
        
        if ($this->annual_revenue < 1000000000) {
            return number_format($this->annual_revenue / 1000000, 1) . ' مليون ريال';
        }
        
        return number_format($this->annual_revenue / 1000000000, 1) . ' مليار ريال';
    }

    public function getCoordinates(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }

    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public function getDistanceFrom($lat, $lng): ?float
    {
        if (!$this->hasCoordinates()) {
            return null;
        }
        
        return $this->calculateDistance($this->latitude, $this->longitude, $lat, $lng);
    }

    public function getSocialMedia(): array
    {
        return $this->social_media ?? [];
    }

    public function getCertifications(): array
    {
        return $this->certifications ?? [];
    }

    public function getAwards(): array
    {
        return $this->awards ?? [];
    }

    public function getWarrantyInfo(): array
    {
        return $this->warranty_info ?? [];
    }

    public function getSupportInfo(): array
    {
        return $this->support_info ?? [];
    }

    public function getFullAddress(): string
    {
        $address = [];
        
        if ($this->address) {
            $address[] = $this->address;
        }
        
        if ($this->city) {
            $address[] = $this->city;
        }
        
        if ($this->country) {
            $address[] = $this->country;
        }
        
        if ($this->postal_code) {
            $address[] = $this->postal_code;
        }
        
        return implode(', ', $address);
    }

    public function getUrl(): string
    {
        return route('brands.show', $this->slug);
    }

    public function getEditUrl(): string
    {
        return route('admin.brands.edit', $this->id);
    }

    public function getDeleteUrl(): string
    {
        return route('admin.brands.destroy', $this->id);
    }

    // Business Logic Methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function hasProducts(): bool
    {
        return $this->products_count > 0;
    }

    public function isParent(): bool
    {
        return $this->subsidiaries()->count() > 0;
    }

    public function isSubsidiary(): bool
    {
        return !is_null($this->parent_company);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function updateProductsCount(): void
    {
        $count = $this->products()->count();
        $this->update(['products_count' => $count]);
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

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function feature(): void
    {
        $this->update(['is_featured' => true]);
    }

    public function unfeature(): void
    {
        $this->update(['is_featured' => false]);
    }

    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    public function unverify(): void
    {
        $this->update(['is_verified' => false]);
    }

    public function moveUp(): void
    {
        $previous = static::where('sort_order', '<', $this->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();
        
        if ($previous) {
            $this->update(['sort_order' => $previous->sort_order]);
            $previous->update(['sort_order' => $this->sort_order]);
        }
    }

    public function moveDown(): void
    {
        $next = static::where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();
        
        if ($next) {
            $this->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $this->sort_order]);
        }
    }

    public function getAverageProductPrice(): float
    {
        $products = $this->products()->where('price', '>', 0);
        
        if ($products->count() == 0) {
            return 0;
        }
        
        return $products->avg('price');
    }

    public function getAverageProductPriceFormatted(): string
    {
        $average = $this->getAverageProductPrice();
        
        if ($average == 0) {
            return 'غير محدد';
        }
        
        return number_format($average, 2) . ' ريال';
    }

    public function getTotalRevenue(): float
    {
        return $this->products()->sum(\DB::raw('price * order_count'));
    }

    public function getTotalRevenueFormatted(): string
    {
        return number_format($this->getTotalRevenue(), 2) . ' ريال';
    }

    public function getTopProducts(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->products()
            ->orderBy('order_count', 'desc')
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getFeaturedProducts(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->products()
            ->where('is_featured', true)
            ->orderBy('sort_order', 'asc')
            ->limit($limit)
            ->get();
    }

    public function getNewProducts(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->products()
            ->where('is_new', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getOnSaleProducts(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->products()
            ->where('is_on_sale', true)
            ->orderBy('discount_amount', 'desc')
            ->limit($limit)
            ->get();
    }

    // Utility Methods
    private function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    // Static Methods
    public static function getFeaturedBrands(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::featured()->active()->ordered()->limit($limit)->get();
    }

    public static function getPopularBrands(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->popular($limit)->get();
    }

    public static function getTopRatedBrands(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->topRated($limit)->get();
    }

    public static function getVerifiedBrands(): \Illuminate\Database\Eloquent\Collection
    {
        return static::verified()->active()->ordered()->get();
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($brand) {
            // Set default values
            if (is_null($brand->is_active)) {
                $brand->is_active = true;
            }
            
            if (is_null($brand->is_featured)) {
                $brand->is_featured = false;
            }
            
            if (is_null($brand->is_verified)) {
                $brand->is_verified = false;
            }
            
            if (is_null($brand->sort_order)) {
                $maxOrder = static::max('sort_order');
                $brand->sort_order = ($maxOrder ?? 0) + 1;
            }
            
            if (is_null($brand->products_count)) {
                $brand->products_count = 0;
            }
            
            if (is_null($brand->view_count)) {
                $brand->view_count = 0;
            }
            
            if (is_null($brand->rating)) {
                $brand->rating = 0;
            }
            
            if (is_null($brand->rating_count)) {
                $brand->rating_count = 0;
            }
            
            // Generate slug if not provided
            if (empty($brand->slug)) {
                $brand->slug = \Str::slug($brand->name);
            }
        });

        static::created(function ($brand) {
            // Clear cache
            Cache::forget('featured_brands');
            Cache::forget('popular_brands');
            Cache::forget('top_rated_brands');
        });

        static::updated(function ($brand) {
            // Clear cache
            Cache::forget('featured_brands');
            Cache::forget('popular_brands');
            Cache::forget('top_rated_brands');
        });

        static::deleted(function ($brand) {
            // Clear cache
            Cache::forget('featured_brands');
            Cache::forget('popular_brands');
            Cache::forget('top_rated_brands');
        });
    }
}