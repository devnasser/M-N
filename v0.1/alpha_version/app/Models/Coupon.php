<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'description',
        'description_en',
        'type',
        'value',
        'min_amount',
        'max_discount',
        'usage_limit',
        'used_count',
        'per_user_limit',
        'is_active',
        'is_featured',
        'starts_at',
        'expires_at',
        'applicable_products',
        'excluded_products',
        'applicable_categories',
        'excluded_categories',
        'applicable_users',
        'excluded_users',
        'first_time_only',
        'new_users_only',
        'loyalty_level_required',
        'minimum_order_count',
        'minimum_spent',
        'maximum_spent',
        'days_valid_after_issue',
        'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'per_user_limit' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applicable_products' => 'array',
        'excluded_products' => 'array',
        'applicable_categories' => 'array',
        'excluded_categories' => 'array',
        'applicable_users' => 'array',
        'excluded_users' => 'array',
        'first_time_only' => 'boolean',
        'new_users_only' => 'boolean',
        'loyalty_level_required' => 'integer',
        'minimum_order_count' => 'integer',
        'minimum_spent' => 'decimal:2',
        'maximum_spent' => 'decimal:2',
        'days_valid_after_issue' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function carts()
    {
        return $this->belongsToMany(Cart::class, 'cart_coupon');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_coupon');
    }

    public function usageHistory()
    {
        return $this->hasMany(CouponUsage::class);
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

    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('starts_at')
              ->orWhere('starts_at', '<=', now());
        })->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>=', now());
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>=', now());
        });
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper($code));
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('usage_limit')
              ->orWhereRaw('used_count < usage_limit');
        });
    }

    public function scopeUnlimited(Builder $query): Builder
    {
        return $query->whereNull('usage_limit');
    }

    public function scopeLimited(Builder $query): Builder
    {
        return $query->whereNotNull('usage_limit');
    }

    public function scopeForNewUsers(Builder $query): Builder
    {
        return $query->where('new_users_only', true);
    }

    public function scopeForFirstTime(Builder $query): Builder
    {
        return $query->where('first_time_only', true);
    }

    public function scopeByLoyaltyLevel(Builder $query, int $level): Builder
    {
        return $query->where('loyalty_level_required', '<=', $level);
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

    public function getStatusBadge(): string
    {
        if (!$this->is_active) {
            return '<span class="badge bg-danger">غير نشط</span>';
        }
        
        if ($this->isExpired()) {
            return '<span class="badge bg-secondary">منتهي الصلاحية</span>';
        }
        
        if ($this->isExhausted()) {
            return '<span class="badge bg-warning">نفذ الاستخدام</span>';
        }
        
        if ($this->is_featured) {
            return '<span class="badge bg-primary">مميز</span>';
        }
        
        return '<span class="badge bg-success">نشط</span>';
    }

    public function getTypeBadge(): string
    {
        $badges = [
            'percentage' => '<span class="badge bg-info">نسبة مئوية</span>',
            'fixed' => '<span class="badge bg-warning">مبلغ ثابت</span>',
            'free_shipping' => '<span class="badge bg-success">شحن مجاني</span>',
            'buy_one_get_one' => '<span class="badge bg-primary">اشتري واحد واحصل على واحد</span>',
        ];
        
        return $badges[$this->type] ?? '<span class="badge bg-secondary">' . $this->type . '</span>';
    }

    public function getValueFormatted(): string
    {
        if ($this->type === 'percentage') {
            return $this->value . '%';
        }
        
        if ($this->type === 'fixed') {
            return number_format($this->value, 2) . ' ريال';
        }
        
        if ($this->type === 'free_shipping') {
            return 'شحن مجاني';
        }
        
        if ($this->type === 'buy_one_get_one') {
            return 'اشتري واحد واحصل على واحد';
        }
        
        return $this->value;
    }

    public function getMinAmountFormatted(): string
    {
        if (!$this->min_amount) {
            return 'لا يوجد حد أدنى';
        }
        
        return number_format($this->min_amount, 2) . ' ريال';
    }

    public function getMaxDiscountFormatted(): string
    {
        if (!$this->max_discount) {
            return 'لا يوجد حد أقصى';
        }
        
        return number_format($this->max_discount, 2) . ' ريال';
    }

    public function getUsageLimitFormatted(): string
    {
        if (!$this->usage_limit) {
            return 'غير محدود';
        }
        
        return $this->usage_limit . ' استخدام';
    }

    public function getUsedCountFormatted(): string
    {
        return $this->used_count . ' استخدام';
    }

    public function getRemainingUsageFormatted(): string
    {
        if (!$this->usage_limit) {
            return 'غير محدود';
        }
        
        $remaining = $this->usage_limit - $this->used_count;
        
        if ($remaining <= 0) {
            return 'نفذ الاستخدام';
        }
        
        return $remaining . ' استخدام متبقي';
    }

    public function getUsagePercentage(): float
    {
        if (!$this->usage_limit) {
            return 0;
        }
        
        return round(($this->used_count / $this->usage_limit) * 100, 2);
    }

    public function getUsagePercentageFormatted(): string
    {
        return $this->getUsagePercentage() . '%';
    }

    public function getStartsAtFormatted(): string
    {
        if (!$this->starts_at) {
            return 'فوري';
        }
        
        return $this->starts_at->format('Y-m-d H:i');
    }

    public function getExpiresAtFormatted(): string
    {
        if (!$this->expires_at) {
            return 'غير محدد';
        }
        
        return $this->expires_at->format('Y-m-d H:i');
    }

    public function getDaysUntilExpiry(): int
    {
        if (!$this->expires_at) {
            return -1;
        }
        
        return now()->diffInDays($this->expires_at, false);
    }

    public function getDaysUntilExpiryFormatted(): string
    {
        $days = $this->getDaysUntilExpiry();
        
        if ($days == -1) {
            return 'غير محدد';
        }
        
        if ($days < 0) {
            return 'منتهي الصلاحية';
        }
        
        if ($days == 0) {
            return 'ينتهي اليوم';
        }
        
        if ($days == 1) {
            return 'ينتهي غداً';
        }
        
        if ($days < 7) {
            return 'ينتهي خلال ' . $days . ' أيام';
        }
        
        $weeks = floor($days / 7);
        $remainingDays = $days % 7;
        
        if ($weeks == 1) {
            return 'ينتهي خلال أسبوع' . ($remainingDays > 0 ? ' و' . $remainingDays . ' أيام' : '');
        }
        
        return 'ينتهي خلال ' . $weeks . ' أسابيع' . ($remainingDays > 0 ? ' و' . $remainingDays . ' أيام' : '');
    }

    public function getApplicableProducts(): array
    {
        return $this->applicable_products ?? [];
    }

    public function getExcludedProducts(): array
    {
        return $this->excluded_products ?? [];
    }

    public function getApplicableCategories(): array
    {
        return $this->applicable_categories ?? [];
    }

    public function getExcludedCategories(): array
    {
        return $this->excluded_categories ?? [];
    }

    public function getApplicableUsers(): array
    {
        return $this->applicable_users ?? [];
    }

    public function getExcludedUsers(): array
    {
        return $this->excluded_users ?? [];
    }

    public function getMinimumSpentFormatted(): string
    {
        if (!$this->minimum_spent) {
            return 'لا يوجد حد أدنى';
        }
        
        return number_format($this->minimum_spent, 2) . ' ريال';
    }

    public function getMaximumSpentFormatted(): string
    {
        if (!$this->maximum_spent) {
            return 'لا يوجد حد أقصى';
        }
        
        return number_format($this->maximum_spent, 2) . ' ريال';
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

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isNotStarted(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }

    public function isValid(): bool
    {
        return $this->isActive() && !$this->isExpired() && !$this->isNotStarted() && !$this->isExhausted();
    }

    public function isExhausted(): bool
    {
        return $this->usage_limit && $this->used_count >= $this->usage_limit;
    }

    public function isUnlimited(): bool
    {
        return !$this->usage_limit;
    }

    public function isForNewUsers(): bool
    {
        return $this->new_users_only;
    }

    public function isForFirstTime(): bool
    {
        return $this->first_time_only;
    }

    public function hasLoyaltyRequirement(): bool
    {
        return $this->loyalty_level_required > 0;
    }

    public function hasOrderCountRequirement(): bool
    {
        return $this->minimum_order_count > 0;
    }

    public function hasSpentRequirement(): bool
    {
        return $this->minimum_spent > 0 || $this->maximum_spent > 0;
    }

    public function canBeUsedByUser(User $user): bool
    {
        // Check if user is excluded
        if (in_array($user->id, $this->getExcludedUsers())) {
            return false;
        }
        
        // Check if user is in applicable users list
        $applicableUsers = $this->getApplicableUsers();
        if (!empty($applicableUsers) && !in_array($user->id, $applicableUsers)) {
            return false;
        }
        
        // Check new users only
        if ($this->isForNewUsers() && $user->getTotalOrders() > 0) {
            return false;
        }
        
        // Check first time only
        if ($this->isForFirstTime() && $user->getTotalOrders() > 1) {
            return false;
        }
        
        // Check loyalty level requirement
        if ($this->hasLoyaltyRequirement() && $user->getLoyaltyLevel() < $this->loyalty_level_required) {
            return false;
        }
        
        // Check minimum order count
        if ($this->hasOrderCountRequirement() && $user->getTotalOrders() < $this->minimum_order_count) {
            return false;
        }
        
        // Check minimum spent
        if ($this->minimum_spent > 0 && $user->getTotalSpent() < $this->minimum_spent) {
            return false;
        }
        
        // Check maximum spent
        if ($this->maximum_spent > 0 && $user->getTotalSpent() > $this->maximum_spent) {
            return false;
        }
        
        // Check per user limit
        if ($this->per_user_limit > 0) {
            $userUsageCount = $this->usageHistory()->where('user_id', $user->id)->count();
            if ($userUsageCount >= $this->per_user_limit) {
                return false;
            }
        }
        
        return true;
    }

    public function canBeAppliedToCart(Cart $cart): bool
    {
        // Check minimum amount
        if ($this->min_amount > 0 && $cart->subtotal < $this->min_amount) {
            return false;
        }
        
        // Check if already applied
        if ($cart->coupons()->where('coupon_id', $this->id)->exists()) {
            return false;
        }
        
        return true;
    }

    public function canBeAppliedToProduct(Product $product): bool
    {
        // Check if product is excluded
        if (in_array($product->id, $this->getExcludedProducts())) {
            return false;
        }
        
        // Check if product is in applicable products list
        $applicableProducts = $this->getApplicableProducts();
        if (!empty($applicableProducts) && !in_array($product->id, $applicableProducts)) {
            return false;
        }
        
        // Check if product category is excluded
        if (in_array($product->category_id, $this->getExcludedCategories())) {
            return false;
        }
        
        // Check if product category is in applicable categories list
        $applicableCategories = $this->getApplicableCategories();
        if (!empty($applicableCategories) && !in_array($product->category_id, $applicableCategories)) {
            return false;
        }
        
        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'percentage') {
            $discount = ($subtotal * $this->value) / 100;
            
            // Apply max discount limit
            if ($this->max_discount > 0) {
                $discount = min($discount, $this->max_discount);
            }
            
            return $discount;
        }
        
        if ($this->type === 'fixed') {
            return min($this->value, $subtotal);
        }
        
        if ($this->type === 'free_shipping') {
            return 0; // Shipping cost will be calculated separately
        }
        
        return 0;
    }

    public function calculateDiscountFormatted(float $subtotal): string
    {
        $discount = $this->calculateDiscount($subtotal);
        return number_format($discount, 2) . ' ريال';
    }

    public function getFinalPrice(float $subtotal): float
    {
        return $subtotal - $this->calculateDiscount($subtotal);
    }

    public function getFinalPriceFormatted(float $subtotal): string
    {
        $finalPrice = $this->getFinalPrice($subtotal);
        return number_format($finalPrice, 2) . ' ريال';
    }

    public function getSavingsPercentage(float $subtotal): float
    {
        if ($subtotal == 0) {
            return 0;
        }
        
        $discount = $this->calculateDiscount($subtotal);
        return round(($discount / $subtotal) * 100, 2);
    }

    public function getSavingsPercentageFormatted(float $subtotal): string
    {
        return $this->getSavingsPercentage($subtotal) . '%';
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    public function decrementUsage(): void
    {
        $this->decrement('used_count');
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

    public function extendExpiry(int $days): void
    {
        if ($this->expires_at) {
            $this->update(['expires_at' => $this->expires_at->addDays($days)]);
        } else {
            $this->update(['expires_at' => now()->addDays($days)]);
        }
    }

    public function setExpiry(int $daysFromNow): void
    {
        $this->update(['expires_at' => now()->addDays($daysFromNow)]);
    }

    public function resetUsage(): void
    {
        $this->update(['used_count' => 0]);
    }

    public function duplicate(): Coupon
    {
        $newCoupon = $this->replicate();
        $newCoupon->code = $this->code . '_COPY';
        $newCoupon->used_count = 0;
        $newCoupon->is_active = false;
        $newCoupon->save();
        
        return $newCoupon;
    }

    // Static Methods
    public static function findByCode(string $code): ?Coupon
    {
        return static::byCode($code)->first();
    }

    public static function getValidCoupons(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->valid()->available()->get();
    }

    public static function getFeaturedCoupons(): \Illuminate\Database\Eloquent\Collection
    {
        return static::featured()->active()->valid()->available()->get();
    }

    public static function getExpiredCoupons(): \Illuminate\Database\Eloquent\Collection
    {
        return static::expired()->get();
    }

    public static function getExpiringSoon(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addDays($days))
            ->get();
    }

    public static function getMostUsed(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('used_count', 'desc')->limit($limit)->get();
    }

    public static function getLeastUsed(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('used_count', 'asc')->limit($limit)->get();
    }

    public static function getConversionRate(): float
    {
        $totalIssued = static::count();
        $totalUsed = static::sum('used_count');
        
        if ($totalIssued == 0) {
            return 0;
        }
        
        return round(($totalUsed / $totalIssued) * 100, 2);
    }

    public static function getAverageUsage(): float
    {
        return static::avg('used_count') ?? 0;
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($coupon) {
            // Set default values
            if (is_null($coupon->is_active)) {
                $coupon->is_active = true;
            }
            
            if (is_null($coupon->is_featured)) {
                $coupon->is_featured = false;
            }
            
            if (is_null($coupon->used_count)) {
                $coupon->used_count = 0;
            }
            
            if (is_null($coupon->per_user_limit)) {
                $coupon->per_user_limit = 1;
            }
            
            if (is_null($coupon->first_time_only)) {
                $coupon->first_time_only = false;
            }
            
            if (is_null($coupon->new_users_only)) {
                $coupon->new_users_only = false;
            }
            
            if (is_null($coupon->loyalty_level_required)) {
                $coupon->loyalty_level_required = 0;
            }
            
            if (is_null($coupon->minimum_order_count)) {
                $coupon->minimum_order_count = 0;
            }
            
            if (is_null($coupon->minimum_spent)) {
                $coupon->minimum_spent = 0;
            }
            
            if (is_null($coupon->maximum_spent)) {
                $coupon->maximum_spent = 0;
            }
            
            if (is_null($coupon->min_amount)) {
                $coupon->min_amount = 0;
            }
            
            if (is_null($coupon->max_discount)) {
                $coupon->max_discount = 0;
            }
            
            // Convert code to uppercase
            if ($coupon->code) {
                $coupon->code = strtoupper($coupon->code);
            }
        });

        static::created(function ($coupon) {
            // Clear cache
            Cache::forget('valid_coupons');
            Cache::forget('featured_coupons');
        });

        static::updated(function ($coupon) {
            // Clear cache
            Cache::forget('valid_coupons');
            Cache::forget('featured_coupons');
        });

        static::deleted(function ($coupon) {
            // Clear cache
            Cache::forget('valid_coupons');
            Cache::forget('featured_coupons');
        });
    }
}