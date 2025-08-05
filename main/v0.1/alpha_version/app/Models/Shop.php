<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'name_en',
        'slug',
        'description',
        'description_en',
        'logo',
        'banner',
        'phone',
        'email',
        'website',
        'address',
        'city',
        'region',
        'postal_code',
        'latitude',
        'longitude',
        'business_hours',
        'payment_methods',
        'delivery_options',
        'is_verified',
        'is_active',
        'is_featured',
        'rating_average',
        'rating_count',
        'total_orders',
        'total_revenue',
        'commission_rate',
        'tax_number',
        'cr_number',
        'bank_info',
        'social_media',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'payment_methods' => 'array',
        'delivery_options' => 'array',
        'social_media' => 'array',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'rating_average' => 'decimal:1',
        'rating_count' => 'integer',
        'total_orders' => 'integer',
        'total_revenue' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewable_id')
            ->where('reviewable_type', Shop::class);
    }

    public function employees()
    {
        return $this->hasMany(User::class)->whereHas('roles', function ($q) {
            $q->where('name', 'shop_employee');
        });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 50)
    {
        return $query->selectRaw('*, 
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance', 
            [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radius)
            ->orderBy('distance');
    }

    // Helper Methods
    public function getAverageRating(): float
    {
        return $this->rating_average ?? 0;
    }

    public function getTotalProducts(): int
    {
        return $this->products()->count();
    }

    public function getTotalOrders(): int
    {
        return $this->total_orders ?? 0;
    }

    public function getTotalRevenue(): float
    {
        return $this->total_revenue ?? 0;
    }

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function getStatusBadge(): string
    {
        if (!$this->is_active) {
            return '<span class="badge bg-danger">غير نشط</span>';
        }
        
        if (!$this->is_verified) {
            return '<span class="badge bg-warning">في انتظار التحقق</span>';
        }
        
        if ($this->is_featured) {
            return '<span class="badge bg-primary">مميز</span>';
        }
        
        return '<span class="badge bg-success">نشط</span>';
    }

    public function getLogoUrl(): string
    {
        return $this->logo 
            ? asset('storage/' . $this->logo)
            : asset('images/default-shop-logo.png');
    }

    public function getBannerUrl(): string
    {
        return $this->banner 
            ? asset('storage/' . $this->banner)
            : asset('images/default-shop-banner.png');
    }

    public function getLocalizedName(): string
    {
        return app()->getLocale() === 'ar' ? $this->name : $this->name_en;
    }

    public function getLocalizedDescription(): string
    {
        return app()->getLocale() === 'ar' ? $this->description : $this->description_en;
    }

    public function getFullAddress(): string
    {
        return $this->address . ', ' . $this->city . ', ' . $this->region;
    }

    public function getBusinessHoursText(): string
    {
        if (empty($this->business_hours)) {
            return 'غير محدد';
        }
        
        $hours = [];
        foreach ($this->business_hours as $day => $time) {
            $hours[] = $day . ': ' . $time;
        }
        
        return implode(', ', $hours);
    }

    public function isOpenNow(): bool
    {
        if (empty($this->business_hours)) {
            return true;
        }
        
        $today = strtolower(now()->format('l'));
        $currentTime = now()->format('H:i');
        
        if (!isset($this->business_hours[$today])) {
            return false;
        }
        
        $hours = $this->business_hours[$today];
        if ($hours === 'closed') {
            return false;
        }
        
        list($open, $close) = explode('-', $hours);
        return $currentTime >= $open && $currentTime <= $close;
    }

    public function updateStats(): void
    {
        $this->update([
            'total_orders' => $this->orders()->count(),
            'total_revenue' => $this->orders()->where('status', 'delivered')->sum('total_amount'),
        ]);
    }

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
} 