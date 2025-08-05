<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'first_name',
        'last_name',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'region',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'is_default',
        'is_billing',
        'is_shipping',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_billing' => 'boolean',
        'is_shipping' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'shipping_address_id');
    }

    // Scopes
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeBilling(Builder $query): Builder
    {
        return $query->where('is_billing', true);
    }

    public function scopeShipping(Builder $query): Builder
    {
        return $query->where('is_shipping', true);
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
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getFullAddress(): string
    {
        $address = $this->address_line_1;
        
        if ($this->address_line_2) {
            $address .= ', ' . $this->address_line_2;
        }
        
        $address .= ', ' . $this->city;
        
        if ($this->region) {
            $address .= ', ' . $this->region;
        }
        
        if ($this->postal_code) {
            $address .= ' ' . $this->postal_code;
        }
        
        if ($this->country) {
            $address .= ', ' . $this->country;
        }
        
        return $address;
    }

    public function getShortAddress(): string
    {
        $address = $this->address_line_1;
        
        if ($this->city) {
            $address .= ', ' . $this->city;
        }
        
        if ($this->region) {
            $address .= ', ' . $this->region;
        }
        
        return $address;
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

    public function isWithinDeliveryRadius($lat, $lng, $radius = 50): bool
    {
        $distance = $this->getDistanceFrom($lat, $lng);
        
        if (is_null($distance)) {
            return false;
        }
        
        return $distance <= $radius;
    }

    public function getTypeBadge(): string
    {
        $badges = [];
        
        if ($this->is_default) {
            $badges[] = '<span class="badge bg-primary">افتراضي</span>';
        }
        
        if ($this->is_billing) {
            $badges[] = '<span class="badge bg-success">فاتورة</span>';
        }
        
        if ($this->is_shipping) {
            $badges[] = '<span class="badge bg-info">شحن</span>';
        }
        
        return implode(' ', $badges);
    }

    public function getStatusBadge(): string
    {
        if ($this->is_default) {
            return '<span class="badge bg-primary">افتراضي</span>';
        }
        return '<span class="badge bg-secondary">عادي</span>';
    }

    // Business Logic Methods
    public function makeDefault(): void
    {
        // Remove default from other addresses
        $this->user->addresses()
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
        
        // Make this address default
        $this->update(['is_default' => true]);
    }

    public function duplicate(): UserAddress
    {
        $newAddress = $this->replicate();
        $newAddress->title = $this->title . ' (نسخة)';
        $newAddress->is_default = false;
        $newAddress->save();
        
        return $newAddress;
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

    // Events
    protected static function booted()
    {
        static::creating(function ($address) {
            // Set default values
            if (is_null($address->country)) {
                $address->country = 'Saudi Arabia';
            }
            
            if (is_null($address->is_billing)) {
                $address->is_billing = true;
            }
            
            if (is_null($address->is_shipping)) {
                $address->is_shipping = true;
            }
        });

        static::created(function ($address) {
            // If this is the first address, make it default
            if ($address->user->addresses()->count() === 1) {
                $address->makeDefault();
            }
        });

        static::updated(function ($address) {
            // If this address is now default, remove default from others
            if ($address->is_default) {
                $address->user->addresses()
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}