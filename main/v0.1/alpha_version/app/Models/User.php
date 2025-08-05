<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'last_login_at' => 'datetime',
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
        return $this->belongsToMany(Product::class, 'favorites');
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

    public function getDashboardRoute(): string
    {
        if ($this->isAdmin()) return '/admin/dashboard';
        if ($this->isShop()) return '/shop/dashboard';
        if ($this->isDriver()) return '/driver/dashboard';
        if ($this->isTechnician()) return '/technician/dashboard';
        return '/buyer/dashboard';
    }

    public function getFullName(): string
    {
        return $this->name;
    }

    public function getProfileImageUrl(): string
    {
        return $this->profile_image 
            ? asset('storage/' . $this->profile_image)
            : asset('images/default-avatar.png');
    }

    public function getTotalOrders(): int
    {
        return $this->orders()->count();
    }

    public function getTotalSpent(): float
    {
        return $this->orders()->where('status', 'delivered')->sum('total_amount');
    }

    public function getAverageRating(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
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
}
