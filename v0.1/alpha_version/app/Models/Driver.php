<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'vehicle_model',
        'vehicle_year',
        'license_number',
        'license_expiry',
        'insurance_number',
        'insurance_expiry',
        'current_location',
        'latitude',
        'longitude',
        'is_available',
        'is_verified',
        'is_active',
        'rating_average',
        'rating_count',
        'total_deliveries',
        'total_earnings',
        'commission_rate',
        'preferred_areas',
        'working_hours',
        'payment_info',
        'documents',
        'meta_data',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'insurance_expiry' => 'date',
        'current_location' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_available' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'rating_average' => 'decimal:1',
        'rating_count' => 'integer',
        'total_deliveries' => 'integer',
        'total_earnings' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'preferred_areas' => 'array',
        'working_hours' => 'array',
        'payment_info' => 'array',
        'documents' => 'array',
        'meta_data' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Order::class);
    }

    public function deliveryBids()
    {
        return $this->hasMany(DeliveryBid::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewable_id')
            ->where('reviewable_type', Driver::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 10)
    {
        return $query->selectRaw('*, 
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance', 
            [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radius)
            ->orderBy('distance');
    }

    public function scopeByVehicleType($query, $type)
    {
        return $query->where('vehicle_type', $type);
    }

    // Helper Methods
    public function getAverageRating(): float
    {
        return $this->rating_average ?? 0;
    }

    public function getTotalDeliveries(): int
    {
        return $this->total_deliveries ?? 0;
    }

    public function getTotalEarnings(): float
    {
        return $this->total_earnings ?? 0;
    }

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function isAvailable(): bool
    {
        return $this->is_available && $this->is_active;
    }

    public function getStatusBadge(): string
    {
        if (!$this->is_active) {
            return '<span class="badge bg-danger">غير نشط</span>';
        }
        
        if (!$this->is_verified) {
            return '<span class="badge bg-warning">في انتظار التحقق</span>';
        }
        
        if (!$this->is_available) {
            return '<span class="badge bg-secondary">غير متاح</span>';
        }
        
        return '<span class="badge bg-success">متاح</span>';
    }

    public function getAvailabilityBadge(): string
    {
        return $this->is_available 
            ? '<span class="badge bg-success">متاح</span>'
            : '<span class="badge bg-secondary">غير متاح</span>';
    }

    public function canDeliverTo(float $latitude, float $longitude): bool
    {
        if (empty($this->preferred_areas)) {
            return true;
        }

        foreach ($this->preferred_areas as $area) {
            $distance = $this->calculateDistance(
                $latitude, 
                $longitude, 
                $area['latitude'], 
                $area['longitude']
            );
            
            if ($distance <= ($area['radius'] ?? 10)) {
                return true;
            }
        }
        
        return false;
    }

    public function getCurrentDeliveries(): int
    {
        return $this->deliveries()
            ->whereIn('status', ['confirmed', 'processing', 'shipped'])
            ->count();
    }

    public function getCompletedDeliveriesToday(): int
    {
        return $this->deliveries()
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->count();
    }

    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'updated_at' => now(),
            ],
        ]);
    }

    public function updateStats(): void
    {
        $this->update([
            'total_deliveries' => $this->deliveries()->where('status', 'delivered')->count(),
            'total_earnings' => $this->deliveries()
                ->where('status', 'delivered')
                ->sum('shipping_amount'),
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

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles * 1.609344; // Convert to kilometers
    }

    public function getVehicleInfo(): string
    {
        return $this->vehicle_year . ' ' . $this->vehicle_model . ' (' . $this->vehicle_type . ')';
    }

    public function isLicenseExpired(): bool
    {
        return $this->license_expiry && $this->license_expiry->isPast();
    }

    public function isInsuranceExpired(): bool
    {
        return $this->insurance_expiry && $this->insurance_expiry->isPast();
    }

    public function getDocumentsStatus(): array
    {
        return [
            'license' => !$this->isLicenseExpired(),
            'insurance' => !$this->isInsuranceExpired(),
            'documents' => !empty($this->documents),
        ];
    }
} 