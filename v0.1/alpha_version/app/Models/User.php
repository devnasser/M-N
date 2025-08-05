<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'national_id',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'region',
        'postal_code',
        'profile_image',
        'is_verified',
        'is_active',
        'last_login_at',
        'preferences',
        'language',
        'timezone',
        'email_verified_at',
        'phone_verified_at',
        'verification_token',
        'reset_token',
        'reset_token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
        'reset_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'last_login_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'preferences' => 'array',
    ];

    // Relationships
    public function shop()
    {
        return $this->hasOne(Shop::class);
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function technician()
    {
        return $this->hasOne(Technician::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function scopeByRegion(Builder $query, string $region): Builder
    {
        return $query->where('region', $region);
    }

    // Helper Methods
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isShop(): bool
    {
        return $this->hasRole('shop');
    }

    public function isDriver(): bool
    {
        return $this->hasRole('driver');
    }

    public function isBuyer(): bool
    {
        return $this->hasRole('buyer');
    }

    public function isTechnician(): bool
    {
        return $this->hasRole('technician');
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->hasAnyRole($roles);
    }

    public function getDashboardRoute(): string
    {
        if ($this->isAdmin()) return route('admin.dashboard');
        if ($this->isShop()) return route('shop.dashboard');
        if ($this->isDriver()) return route('driver.dashboard');
        if ($this->isTechnician()) return route('technician.dashboard');
        return route('buyer.dashboard');
    }

    public function getFullName(): string
    {
        return $this->name;
    }

    public function getProfileImageUrl(): string
    {
        if ($this->profile_image && file_exists(storage_path('app/public/' . $this->profile_image))) {
            return asset('storage/' . $this->profile_image);
        }
        return asset('images/default-avatar.png');
    }

    public function getTotalOrders(): int
    {
        return Cache::remember("user_orders_count_{$this->id}", 300, function () {
            return $this->orders()->count();
        });
    }

    public function getTotalSpent(): float
    {
        return Cache::remember("user_total_spent_{$this->id}", 300, function () {
            return $this->orders()->where('status', 'delivered')->sum('total_amount');
        });
    }

    public function getAverageRating(): float
    {
        return Cache::remember("user_avg_rating_{$this->id}", 300, function () {
            return round($this->reviews()->avg('rating') ?? 0, 1);
        });
    }

    public function getReviewsCount(): int
    {
        return Cache::remember("user_reviews_count_{$this->id}", 300, function () {
            return $this->reviews()->count();
        });
    }

    public function getFavoritesCount(): int
    {
        return Cache::remember("user_favorites_count_{$this->id}", 300, function () {
            return $this->favorites()->count();
        });
    }

    public function getCartItemsCount(): int
    {
        return Cache::remember("user_cart_count_{$this->id}", 60, function () {
            return $this->cart()->sum('quantity');
        });
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function getPreferences($key = null, $default = null)
    {
        if ($key === null) {
            return $this->preferences ?? [];
        }
        
        return data_get($this->preferences, $key, $default);
    }

    public function setPreferences($key, $value): void
    {
        $preferences = $this->preferences ?? [];
        data_set($preferences, $key, $value);
        $this->update(['preferences' => $preferences]);
    }

    public function getAge(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        
        return $this->date_of_birth->age;
    }

    public function isEmailVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function isPhoneVerified(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    public function isFullyVerified(): bool
    {
        return $this->isEmailVerified() && $this->isPhoneVerified();
    }

    public function getVerificationStatus(): string
    {
        if ($this->isFullyVerified()) {
            return 'verified';
        } elseif ($this->isEmailVerified()) {
            return 'email_verified';
        } elseif ($this->isPhoneVerified()) {
            return 'phone_verified';
        }
        return 'unverified';
    }

    public function getVerificationBadge(): string
    {
        $status = $this->getVerificationStatus();
        $badges = [
            'verified' => '<span class="badge bg-success">متحقق</span>',
            'email_verified' => '<span class="badge bg-warning">بريد محقق</span>',
            'phone_verified' => '<span class="badge bg-info">هاتف محقق</span>',
            'unverified' => '<span class="badge bg-secondary">غير محقق</span>',
        ];
        
        return $badges[$status] ?? $badges['unverified'];
    }

    public function getStatusBadge(): string
    {
        if ($this->is_active) {
            return '<span class="badge bg-success">نشط</span>';
        }
        return '<span class="badge bg-danger">غير نشط</span>';
    }

    public function getRoleBadge(): string
    {
        $role = $this->roles->first();
        if (!$role) {
            return '<span class="badge bg-secondary">بدون دور</span>';
        }
        
        $badges = [
            'admin' => '<span class="badge bg-danger">مدير</span>',
            'shop' => '<span class="badge bg-primary">متجر</span>',
            'buyer' => '<span class="badge bg-success">مشتري</span>',
            'driver' => '<span class="badge bg-warning">سائق</span>',
            'technician' => '<span class="badge bg-info">فني</span>',
        ];
        
        return $badges[$role->name] ?? '<span class="badge bg-secondary">' . $role->name . '</span>';
    }

    public function getLastLoginFormatted(): string
    {
        if (!$this->last_login_at) {
            return 'لم يسجل دخول من قبل';
        }
        
        return $this->last_login_at->diffForHumans();
    }

    public function getCreatedAtFormatted(): string
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtFormatted(): string
    {
        return $this->updated_at->format('Y-m-d H:i:s');
    }

    // Business Logic Methods
    public function canPlaceOrder(): bool
    {
        return $this->is_active && $this->isFullyVerified();
    }

    public function canReview(): bool
    {
        return $this->is_active && $this->getTotalOrders() > 0;
    }

    public function getLoyaltyLevel(): string
    {
        $totalSpent = $this->getTotalSpent();
        
        if ($totalSpent >= 10000) {
            return 'gold';
        } elseif ($totalSpent >= 5000) {
            return 'silver';
        } elseif ($totalSpent >= 1000) {
            return 'bronze';
        }
        
        return 'new';
    }

    public function getLoyaltyDiscount(): float
    {
        $level = $this->getLoyaltyLevel();
        $discounts = [
            'gold' => 15.0,
            'silver' => 10.0,
            'bronze' => 5.0,
            'new' => 0.0,
        ];
        
        return $discounts[$level] ?? 0.0;
    }

    // Events
    protected static function booted()
    {
        static::created(function ($user) {
            // Clear cache when user is created
            Cache::forget('total_users_count');
        });

        static::updated(function ($user) {
            // Clear user-specific cache when updated
            Cache::forget("user_orders_count_{$user->id}");
            Cache::forget("user_total_spent_{$user->id}");
            Cache::forget("user_avg_rating_{$user->id}");
            Cache::forget("user_reviews_count_{$user->id}");
            Cache::forget("user_favorites_count_{$user->id}");
        });

        static::deleted(function ($user) {
            // Clear all user-related cache when deleted
            Cache::forget("user_orders_count_{$user->id}");
            Cache::forget("user_total_spent_{$user->id}");
            Cache::forget("user_avg_rating_{$user->id}");
            Cache::forget("user_reviews_count_{$user->id}");
            Cache::forget("user_favorites_count_{$user->id}");
            Cache::forget("user_cart_count_{$user->id}");
            Cache::forget('total_users_count');
        });
    }
}
