<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Supplier extends Model
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
        'mobile',
        'fax',
        'address',
        'address_en',
        'city',
        'region',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'contact_person',
        'contact_person_en',
        'contact_email',
        'contact_phone',
        'contact_mobile',
        'tax_number',
        'commercial_number',
        'is_active',
        'is_featured',
        'is_verified',
        'is_preferred',
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
        'employees_count',
        'annual_revenue',
        'payment_terms',
        'credit_limit',
        'current_balance',
        'social_media',
        'certifications',
        'awards',
        'warranty_info',
        'support_info',
        'delivery_info',
        'return_policy',
        'notes',
        'notes_en',
        'metadata',
    ];

    protected $hidden = [
        'tax_number',
        'commercial_number',
        'credit_limit',
        'current_balance',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'is_preferred' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating' => 'decimal:2',
        'founded_year' => 'integer',
        'employees_count' => 'integer',
        'annual_revenue' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'social_media' => 'array',
        'certifications' => 'array',
        'awards' => 'array',
        'warranty_info' => 'array',
        'support_info' => 'array',
        'delivery_info' => 'array',
        'return_policy' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
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
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function parentCompany()
    {
        return $this->belongsTo(Supplier::class, 'parent_company');
    }

    public function subsidiaries()
    {
        return $this->hasMany(Supplier::class, 'parent_company');
    }

    public function contacts()
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function addresses()
    {
        return $this->hasMany(SupplierAddress::class);
    }

    public function documents()
    {
        return $this->hasMany(SupplierDocument::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Scopes
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified(Builder $query)
    {
        return $query->where('is_verified', true);
    }

    public function scopePreferred(Builder $query)
    {
        return $query->where('is_preferred', true);
    }

    public function scopeByCountry(Builder $query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByCity(Builder $query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByRegion(Builder $query, $region)
    {
        return $query->where('region', $region);
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->whereHas('orders');
    }

    public function scopePopular(Builder $query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    public function scopeMostViewed(Builder $query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    public function scopeTopRated(Builder $query)
    {
        return $query->orderBy('rating', 'desc');
    }

    public function scopeByRating(Builder $query, $minRating = 0)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeByEmployeesCount(Builder $query, $minCount = 0)
    {
        return $query->where('employees_count', '>=', $minCount);
    }

    public function scopeByRevenue(Builder $query, $minRevenue = 0)
    {
        return $query->where('annual_revenue', '>=', $minRevenue);
    }

    public function scopeByFoundedYear(Builder $query, $year)
    {
        return $query->where('founded_year', $year);
    }

    public function scopeEstablished(Builder $query, $years = 10)
    {
        $cutoffYear = Carbon::now()->year - $years;
        return $query->where('founded_year', '<=', $cutoffYear);
    }

    public function scopeNew(Builder $query, $years = 5)
    {
        $cutoffYear = Carbon::now()->year - $years;
        return $query->where('founded_year', '>', $cutoffYear);
    }

    public function scopeWithProducts(Builder $query)
    {
        return $query->whereHas('products');
    }

    public function scopeWithoutProducts(Builder $query)
    {
        return $query->whereDoesntHave('products');
    }

    public function scopeWithOrders(Builder $query)
    {
        return $query->whereHas('orders');
    }

    public function scopeWithoutOrders(Builder $query)
    {
        return $query->whereDoesntHave('orders');
    }

    public function scopeWithReviews(Builder $query)
    {
        return $query->whereHas('reviews');
    }

    public function scopeWithoutReviews(Builder $query)
    {
        return $query->whereDoesntHave('reviews');
    }

    public function scopeSearch(Builder $query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('name_en', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('description_en', 'like', "%{$search}%")
                ->orWhere('contact_person', 'like', "%{$search}%")
                ->orWhere('contact_person_en', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")
                ->orWhere('country', 'like', "%{$search}%");
        });
    }

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
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

    public function getShortDescriptionAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->short_description) {
            return $this->short_description;
        }
        
        return $value ?: $this->short_description_en;
    }

    public function getAddressAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->address) {
            return $this->address;
        }
        
        return $value ?: $this->address_en;
    }

    public function getContactPersonAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->contact_person) {
            return $this->contact_person;
        }
        
        return $value ?: $this->contact_person_en;
    }

    public function getNotesAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->notes) {
            return $this->notes;
        }
        
        return $value ?: $this->notes_en;
    }

    public function getNameArAttribute()
    {
        return $this->name;
    }

    public function getDescriptionArAttribute()
    {
        return $this->description;
    }

    public function getShortDescriptionArAttribute()
    {
        return $this->short_description;
    }

    public function getAddressArAttribute()
    {
        return $this->address;
    }

    public function getContactPersonArAttribute()
    {
        return $this->contact_person;
    }

    public function getNotesArAttribute()
    {
        return $this->notes;
    }

    public function getNameEnAttribute()
    {
        return $this->name_en;
    }

    public function getDescriptionEnAttribute()
    {
        return $this->description_en;
    }

    public function getShortDescriptionEnAttribute()
    {
        return $this->short_description_en;
    }

    public function getAddressEnAttribute()
    {
        return $this->address_en;
    }

    public function getContactPersonEnAttribute()
    {
        return $this->contact_person_en;
    }

    public function getNotesEnAttribute()
    {
        return $this->notes_en;
    }

    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return null;
        }

        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        return asset('storage/' . $this->logo);
    }

    public function getBannerImageUrlAttribute()
    {
        if (!$this->banner_image) {
            return null;
        }

        if (filter_var($this->banner_image, FILTER_VALIDATE_URL)) {
            return $this->banner_image;
        }

        return asset('storage/' . $this->banner_image);
    }

    public function getAgeAttribute()
    {
        if (!$this->founded_year) {
            return null;
        }
        
        return Carbon::now()->year - $this->founded_year;
    }

    public function getAgeFormattedAttribute()
    {
        $age = $this->getAgeAttribute();
        
        if (!$age) {
            return null;
        }
        
        return $age . ' years';
    }

    public function getAgeFormattedArAttribute()
    {
        $age = $this->getAgeAttribute();
        
        if (!$age) {
            return null;
        }
        
        return $age . ' سنة';
    }

    public function getEmployeesCountFormattedAttribute()
    {
        if (!$this->employees_count) {
            return null;
        }
        
        return number_format($this->employees_count);
    }

    public function getEmployeesCountFormattedArAttribute()
    {
        return $this->getEmployeesCountFormattedAttribute();
    }

    public function getAnnualRevenueFormattedAttribute()
    {
        if (!$this->annual_revenue) {
            return null;
        }
        
        return number_format($this->annual_revenue, 2) . ' SAR';
    }

    public function getAnnualRevenueFormattedArAttribute()
    {
        if (!$this->annual_revenue) {
            return null;
        }
        
        return number_format($this->annual_revenue, 2) . ' ريال';
    }

    public function getCreditLimitFormattedAttribute()
    {
        if (!$this->credit_limit) {
            return null;
        }
        
        return number_format($this->credit_limit, 2) . ' SAR';
    }

    public function getCreditLimitFormattedArAttribute()
    {
        if (!$this->credit_limit) {
            return null;
        }
        
        return number_format($this->credit_limit, 2) . ' ريال';
    }

    public function getCurrentBalanceFormattedAttribute()
    {
        if (!$this->current_balance) {
            return null;
        }
        
        return number_format($this->current_balance, 2) . ' SAR';
    }

    public function getCurrentBalanceFormattedArAttribute()
    {
        if (!$this->current_balance) {
            return null;
        }
        
        return number_format($this->current_balance, 2) . ' ريال';
    }

    public function getAvailableCreditAttribute()
    {
        if (!$this->credit_limit) {
            return null;
        }
        
        return $this->credit_limit - $this->current_balance;
    }

    public function getAvailableCreditFormattedAttribute()
    {
        $available = $this->getAvailableCreditAttribute();
        
        if ($available === null) {
            return null;
        }
        
        return number_format($available, 2) . ' SAR';
    }

    public function getAvailableCreditFormattedArAttribute()
    {
        $available = $this->getAvailableCreditAttribute();
        
        if ($available === null) {
            return null;
        }
        
        return number_format($available, 2) . ' ريال';
    }

    public function getCreditUtilizationPercentageAttribute()
    {
        if (!$this->credit_limit || $this->credit_limit <= 0) {
            return 0;
        }
        
        return round(($this->current_balance / $this->credit_limit) * 100, 2);
    }

    public function getFullAddressAttribute()
    {
        $parts = [];
        
        if ($this->address) {
            $parts[] = $this->address;
        }
        
        if ($this->city) {
            $parts[] = $this->city;
        }
        
        if ($this->region) {
            $parts[] = $this->region;
        }
        
        if ($this->postal_code) {
            $parts[] = $this->postal_code;
        }
        
        if ($this->country) {
            $parts[] = $this->country;
        }
        
        return implode(', ', $parts);
    }

    public function getFullAddressArAttribute()
    {
        return $this->getFullAddressAttribute();
    }

    public function getCoordinatesAttribute()
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }
        
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }

    public function hasCoordinatesAttribute()
    {
        return $this->latitude && $this->longitude;
    }

    public function getDistanceFrom($lat, $lng)
    {
        if (!$this->hasCoordinatesAttribute()) {
            return null;
        }
        
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $latDelta = deg2rad($lat - $this->latitude);
        $lngDelta = deg2rad($lng - $this->longitude);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    public function getDistanceFromFormatted($lat, $lng)
    {
        $distance = $this->getDistanceFrom($lat, $lng);
        
        if ($distance === null) {
            return null;
        }
        
        if ($distance < 1) {
            return round($distance * 1000, 0) . ' m';
        }
        
        return round($distance, 1) . ' km';
    }

    public function getDistanceFromFormattedAr($lat, $lng)
    {
        $distance = $this->getDistanceFrom($lat, $lng);
        
        if ($distance === null) {
            return null;
        }
        
        if ($distance < 1) {
            return round($distance * 1000, 0) . ' م';
        }
        
        return round($distance, 1) . ' كم';
    }

    public function getSocialMediaLinksAttribute()
    {
        if (!$this->social_media) {
            return [];
        }
        
        $links = [];
        foreach ($this->social_media as $platform => $username) {
            switch ($platform) {
                case 'facebook':
                    $links[$platform] = "https://facebook.com/{$username}";
                    break;
                case 'twitter':
                    $links[$platform] = "https://twitter.com/{$username}";
                    break;
                case 'instagram':
                    $links[$platform] = "https://instagram.com/{$username}";
                    break;
                case 'linkedin':
                    $links[$platform] = "https://linkedin.com/company/{$username}";
                    break;
                case 'youtube':
                    $links[$platform] = "https://youtube.com/{$username}";
                    break;
                default:
                    $links[$platform] = $username;
            }
        }
        
        return $links;
    }

    public function getStatusBadgeAttribute()
    {
        return $this->is_active 
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-danger">Inactive</span>';
    }

    public function getStatusBadgeArAttribute()
    {
        return $this->is_active 
            ? '<span class="badge bg-success">نشط</span>'
            : '<span class="badge bg-danger">غير نشط</span>';
    }

    public function getVerifiedBadgeAttribute()
    {
        return $this->is_verified 
            ? '<span class="badge bg-primary">Verified</span>'
            : '<span class="badge bg-secondary">Unverified</span>';
    }

    public function getVerifiedBadgeArAttribute()
    {
        return $this->is_verified 
            ? '<span class="badge bg-primary">موثق</span>'
            : '<span class="badge bg-secondary">غير موثق</span>';
    }

    public function getFeaturedBadgeAttribute()
    {
        return $this->is_featured 
            ? '<span class="badge bg-warning">Featured</span>'
            : '<span class="badge bg-secondary">Regular</span>';
    }

    public function getFeaturedBadgeArAttribute()
    {
        return $this->is_featured 
            ? '<span class="badge bg-warning">مميز</span>'
            : '<span class="badge bg-secondary">عادي</span>';
    }

    public function getPreferredBadgeAttribute()
    {
        return $this->is_preferred 
            ? '<span class="badge bg-info">Preferred</span>'
            : '<span class="badge bg-secondary">Regular</span>';
    }

    public function getPreferredBadgeArAttribute()
    {
        return $this->is_preferred 
            ? '<span class="badge bg-info">مفضل</span>'
            : '<span class="badge bg-secondary">عادي</span>';
    }

    public function getRatingStarsAttribute()
    {
        if (!$this->rating) {
            return '<span class="text-muted">No ratings</span>';
        }
        
        $stars = '';
        $fullStars = floor($this->rating);
        $halfStar = $this->rating - $fullStars >= 0.5;
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $fullStars) {
                $stars .= '<i class="fas fa-star text-warning"></i>';
            } elseif ($i == $fullStars + 1 && $halfStar) {
                $stars .= '<i class="fas fa-star-half-alt text-warning"></i>';
            } else {
                $stars .= '<i class="far fa-star text-warning"></i>';
            }
        }
        
        return $stars . ' <span class="text-muted">(' . $this->rating_count . ')</span>';
    }

    public function getRatingStarsArAttribute()
    {
        return $this->getRatingStarsAttribute();
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

    public function isVerified()
    {
        return $this->is_verified;
    }

    public function isPreferred()
    {
        return $this->is_preferred;
    }

    public function hasProducts()
    {
        return $this->products_count > 0;
    }

    public function hasOrders()
    {
        return $this->orders()->exists();
    }

    public function hasReviews()
    {
        return $this->reviews()->exists();
    }

    public function isParent()
    {
        return $this->subsidiaries()->exists();
    }

    public function isSubsidiary()
    {
        return $this->parent_company !== null;
    }

    public function hasLogo()
    {
        return !empty($this->logo);
    }

    public function hasBannerImage()
    {
        return !empty($this->banner_image);
    }

    public function hasWebsite()
    {
        return !empty($this->website_url);
    }

    public function hasSocialMedia()
    {
        return !empty($this->social_media);
    }

    public function hasCertifications()
    {
        return !empty($this->certifications);
    }

    public function hasAwards()
    {
        return !empty($this->awards);
    }

    public function canBeEdited()
    {
        return true;
    }

    public function canBeDeleted()
    {
        return !$this->hasProducts() && !$this->hasOrders();
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

    public function verify()
    {
        $this->update(['is_verified' => true]);
        $this->clearCache();
    }

    public function unverify()
    {
        $this->update(['is_verified' => false]);
        $this->clearCache();
    }

    public function markAsPreferred()
    {
        $this->update(['is_preferred' => true]);
        $this->clearCache();
    }

    public function unmarkAsPreferred()
    {
        $this->update(['is_preferred' => false]);
        $this->clearCache();
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
        $this->clearCache();
    }

    public function updateProductsCount()
    {
        $count = $this->products()->count();
        $this->update(['products_count' => $count]);
        $this->clearCache();
    }

    public function updateRating()
    {
        $avgRating = $this->reviews()->avg('rating') ?: 0;
        $ratingCount = $this->reviews()->count();
        
        $this->update([
            'rating' => $avgRating,
            'rating_count' => $ratingCount,
        ]);
        $this->clearCache();
    }

    public function addSocialMedia($platform, $username)
    {
        $socialMedia = $this->social_media ?: [];
        $socialMedia[$platform] = $username;
        $this->update(['social_media' => $socialMedia]);
        $this->clearCache();
    }

    public function removeSocialMedia($platform)
    {
        $socialMedia = $this->social_media ?: [];
        unset($socialMedia[$platform]);
        $this->update(['social_media' => $socialMedia]);
        $this->clearCache();
    }

    public function addCertification($certification)
    {
        $certifications = $this->certifications ?: [];
        $certifications[] = $certification;
        $this->update(['certifications' => $certifications]);
        $this->clearCache();
    }

    public function removeCertification($certification)
    {
        $certifications = $this->certifications ?: [];
        $certifications = array_diff($certifications, [$certification]);
        $this->update(['certifications' => array_values($certifications)]);
        $this->clearCache();
    }

    public function addAward($award)
    {
        $awards = $this->awards ?: [];
        $awards[] = $award;
        $this->update(['awards' => $awards]);
        $this->clearCache();
    }

    public function removeAward($award)
    {
        $awards = $this->awards ?: [];
        $awards = array_diff($awards, [$award]);
        $this->update(['awards' => array_values($awards)]);
        $this->clearCache();
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->slug = $this->slug . '_copy';
        $duplicate->is_active = false;
        $duplicate->is_featured = false;
        $duplicate->is_verified = false;
        $duplicate->is_preferred = false;
        $duplicate->products_count = 0;
        $duplicate->view_count = 0;
        $duplicate->rating = 0;
        $duplicate->rating_count = 0;
        $duplicate->save();
        
        return $duplicate;
    }

    public function getTotalRevenue()
    {
        return Cache::remember("supplier_revenue_{$this->id}", 3600, function () {
            return $this->orders()->sum('total_amount');
        });
    }

    public function getTotalRevenueFormatted()
    {
        return number_format($this->getTotalRevenue(), 2) . ' SAR';
    }

    public function getTotalRevenueFormattedAr()
    {
        return number_format($this->getTotalRevenue(), 2) . ' ريال';
    }

    public function getAverageOrderValue()
    {
        return Cache::remember("supplier_avg_order_{$this->id}", 3600, function () {
            return $this->orders()->avg('total_amount') ?: 0;
        });
    }

    public function getAverageOrderValueFormatted()
    {
        return number_format($this->getAverageOrderValue(), 2) . ' SAR';
    }

    public function getAverageOrderValueFormattedAr()
    {
        return number_format($this->getAverageOrderValue(), 2) . ' ريال';
    }

    public function getTopProducts($limit = 5)
    {
        return Cache::remember("supplier_top_products_{$this->id}", 3600, function () use ($limit) {
            return $this->products()
                ->withCount('orderItems')
                ->orderBy('order_items_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public function getFeaturedProducts($limit = 5)
    {
        return Cache::remember("supplier_featured_products_{$this->id}", 3600, function () use ($limit) {
            return $this->products()
                ->where('is_featured', true)
                ->limit($limit)
                ->get();
        });
    }

    public function getNewProducts($limit = 5)
    {
        return Cache::remember("supplier_new_products_{$this->id}", 3600, function () use ($limit) {
            return $this->products()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public function getOnSaleProducts($limit = 5)
    {
        return Cache::remember("supplier_onsale_products_{$this->id}", 3600, function () use ($limit) {
            return $this->products()
                ->where('is_on_sale', true)
                ->limit($limit)
                ->get();
        });
    }

    // Static Methods
    public static function getSuppliersCount()
    {
        return Cache::remember('suppliers_count', 3600, function () {
            return static::count();
        });
    }

    public static function getActiveSuppliersCount()
    {
        return Cache::remember('active_suppliers_count', 3600, function () {
            return static::where('is_active', true)->count();
        });
    }

    public static function getVerifiedSuppliersCount()
    {
        return Cache::remember('verified_suppliers_count', 3600, function () {
            return static::where('is_verified', true)->count();
        });
    }

    public static function getFeaturedSuppliersCount()
    {
        return Cache::remember('featured_suppliers_count', 3600, function () {
            return static::where('is_featured', true)->count();
        });
    }

    public static function getPreferredSuppliersCount()
    {
        return Cache::remember('preferred_suppliers_count', 3600, function () {
            return static::where('is_preferred', true)->count();
        });
    }

    public static function findBySlug($slug)
    {
        return static::where('slug', $slug)->first();
    }

    public static function getFeaturedSuppliers()
    {
        return static::where('is_featured', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public static function getPreferredSuppliers()
    {
        return static::where('is_preferred', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public static function getVerifiedSuppliers()
    {
        return static::where('is_verified', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public static function getTopRatedSuppliers($limit = 10)
    {
        return static::where('rating', '>', 0)
            ->where('is_active', true)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getMostViewedSuppliers($limit = 10)
    {
        return static::where('view_count', '>', 0)
            ->where('is_active', true)
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getSuppliersByCountry($country)
    {
        return static::where('country', $country)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public static function getSuppliersByCity($city)
    {
        return static::where('city', $city)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public static function getSuppliersStats()
    {
        return Cache::remember('suppliers_stats', 3600, function () {
            return [
                'total' => static::count(),
                'active' => static::where('is_active', true)->count(),
                'featured' => static::where('is_featured', true)->count(),
                'verified' => static::where('is_verified', true)->count(),
                'preferred' => static::where('is_preferred', true)->count(),
                'with_products' => static::whereHas('products')->count(),
                'with_orders' => static::whereHas('orders')->count(),
                'with_reviews' => static::whereHas('reviews')->count(),
                'established' => static::where('founded_year', '<=', Carbon::now()->year - 10)->count(),
                'new' => static::where('founded_year', '>', Carbon::now()->year - 5)->count(),
                'total_products' => static::sum('products_count'),
                'total_views' => static::sum('view_count'),
                'avg_rating' => static::where('rating', '>', 0)->avg('rating'),
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        Cache::forget('suppliers_count');
        Cache::forget('active_suppliers_count');
        Cache::forget('verified_suppliers_count');
        Cache::forget('featured_suppliers_count');
        Cache::forget('preferred_suppliers_count');
        Cache::forget('suppliers_stats');
        
        Cache::forget("supplier_revenue_{$this->id}");
        Cache::forget("supplier_avg_order_{$this->id}");
        Cache::forget("supplier_top_products_{$this->id}");
        Cache::forget("supplier_featured_products_{$this->id}");
        Cache::forget("supplier_new_products_{$this->id}");
        Cache::forget("supplier_onsale_products_{$this->id}");
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($supplier) {
            if (!isset($supplier->is_active)) {
                $supplier->is_active = true;
            }
            
            if (!isset($supplier->is_featured)) {
                $supplier->is_featured = false;
            }
            
            if (!isset($supplier->is_verified)) {
                $supplier->is_verified = false;
            }
            
            if (!isset($supplier->is_preferred)) {
                $supplier->is_preferred = false;
            }
            
            if (!$supplier->slug) {
                $supplier->slug = \Str::slug($supplier->name ?: $supplier->name_en);
            }
        });

        static::created(function ($supplier) {
            $supplier->clearCache();
        });

        static::updated(function ($supplier) {
            $supplier->clearCache();
        });

        static::deleted(function ($supplier) {
            $supplier->clearCache();
        });
    }
}