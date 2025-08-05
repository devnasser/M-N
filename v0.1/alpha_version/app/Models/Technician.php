<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Technician extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'specialization',
        'experience_years',
        'certifications',
        'skills',
        'availability_schedule',
        'current_location',
        'latitude',
        'longitude',
        'is_available',
        'is_verified',
        'rating',
        'total_orders',
        'total_earnings',
        'commission_rate',
        'status',
        'hire_date',
        'termination_date',
        'emergency_contact',
        'vehicle_info',
        'tools_equipment',
        'insurance_info',
        'background_check',
        'performance_score',
        'metadata',
    ];

    protected $casts = [
        'certifications' => 'array',
        'skills' => 'array',
        'availability_schedule' => 'array',
        'current_location' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_available' => 'boolean',
        'is_verified' => 'boolean',
        'rating' => 'decimal:2',
        'total_orders' => 'integer',
        'total_earnings' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'emergency_contact' => 'array',
        'vehicle_info' => 'array',
        'tools_equipment' => 'array',
        'insurance_info' => 'array',
        'background_check' => 'array',
        'performance_score' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function schedules()
    {
        return $this->hasMany(TechnicianSchedule::class);
    }

    public function earnings()
    {
        return $this->hasMany(TechnicianEarning::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeBySpecialization(Builder $query, string $specialization): Builder
    {
        return $query->where('specialization', $specialization);
    }

    public function scopeByExperience(Builder $query, int $minYears): Builder
    {
        return $query->where('experience_years', '>=', $minYears);
    }

    public function scopeByRating(Builder $query, float $minRating): Builder
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeNearby(Builder $query, float $lat, float $lng, float $radius = 10): Builder
    {
        return $query->whereRaw('
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?
        ', [$lat, $lng, $lat, $radius]);
    }

    public function scopeTopRated(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('rating', 'desc')->limit($limit);
    }

    public function scopeMostExperienced(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('experience_years', 'desc')->limit($limit);
    }

    // Helper Methods
    public function getFullName(): string
    {
        return $this->user->name;
    }

    public function getSpecializationName(): string
    {
        $specializations = [
            'engine' => 'محرك',
            'brakes' => 'فرامل',
            'electrical' => 'كهرباء',
            'ac' => 'تكييف',
            'transmission' => 'ناقل حركة',
            'suspension' => 'تعليق',
            'diagnostic' => 'تشخيص',
            'general' => 'صيانة عامة',
        ];
        
        return $specializations[$this->specialization] ?? $this->specialization;
    }

    public function getSpecializationIcon(): string
    {
        $icons = [
            'engine' => 'fas fa-cog',
            'brakes' => 'fas fa-stop-circle',
            'electrical' => 'fas fa-bolt',
            'ac' => 'fas fa-snowflake',
            'transmission' => 'fas fa-cogs',
            'suspension' => 'fas fa-car',
            'diagnostic' => 'fas fa-search',
            'general' => 'fas fa-tools',
        ];
        
        return $icons[$this->specialization] ?? 'fas fa-tools';
    }

    public function getStatusBadge(): string
    {
        $badges = [
            'active' => '<span class="badge bg-success">نشط</span>',
            'inactive' => '<span class="badge bg-secondary">غير نشط</span>',
            'suspended' => '<span class="badge bg-warning">معلق</span>',
            'terminated' => '<span class="badge bg-danger">منتهي</span>',
            'on_leave' => '<span class="badge bg-info">في إجازة</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge bg-secondary">' . $this->status . '</span>';
    }

    public function getAvailabilityBadge(): string
    {
        if (!$this->is_available) {
            return '<span class="badge bg-danger">غير متاح</span>';
        }
        
        return '<span class="badge bg-success">متاح</span>';
    }

    public function getVerificationBadge(): string
    {
        if ($this->is_verified) {
            return '<span class="badge bg-success">موثق</span>';
        }
        
        return '<span class="badge bg-warning">غير موثق</span>';
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

    public function getExperienceFormatted(): string
    {
        if ($this->experience_years == 0) {
            return 'أقل من سنة';
        }
        
        if ($this->experience_years == 1) {
            return 'سنة واحدة';
        }
        
        return $this->experience_years . ' سنوات';
    }

    public function getTotalEarningsFormatted(): string
    {
        return number_format($this->total_earnings, 2) . ' ريال';
    }

    public function getCommissionRateFormatted(): string
    {
        return $this->commission_rate . '%';
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

    public function getSkillsList(): array
    {
        return $this->skills ?? [];
    }

    public function getCertificationsList(): array
    {
        return $this->certifications ?? [];
    }

    public function getEmergencyContactName(): string
    {
        return $this->emergency_contact['name'] ?? '';
    }

    public function getEmergencyContactPhone(): string
    {
        return $this->emergency_contact['phone'] ?? '';
    }

    public function getVehicleInfo(): array
    {
        return $this->vehicle_info ?? [];
    }

    public function getToolsEquipment(): array
    {
        return $this->tools_equipment ?? [];
    }

    public function getInsuranceInfo(): array
    {
        return $this->insurance_info ?? [];
    }

    public function getBackgroundCheckStatus(): string
    {
        $status = $this->background_check['status'] ?? 'pending';
        
        $statuses = [
            'pending' => 'في الانتظار',
            'passed' => 'مقبول',
            'failed' => 'مرفوض',
            'expired' => 'منتهي الصلاحية',
        ];
        
        return $statuses[$status] ?? $status;
    }

    public function getBackgroundCheckBadge(): string
    {
        $status = $this->background_check['status'] ?? 'pending';
        
        $badges = [
            'pending' => '<span class="badge bg-warning">في الانتظار</span>',
            'passed' => '<span class="badge bg-success">مقبول</span>',
            'failed' => '<span class="badge bg-danger">مرفوض</span>',
            'expired' => '<span class="badge bg-secondary">منتهي الصلاحية</span>',
        ];
        
        return $badges[$status] ?? $badges['pending'];
    }

    public function getPerformanceScoreFormatted(): string
    {
        if (!$this->performance_score) {
            return 'غير محدد';
        }
        
        return number_format($this->performance_score, 1) . '/10';
    }

    public function getPerformanceBadge(): string
    {
        if (!$this->performance_score) {
            return '<span class="badge bg-secondary">غير محدد</span>';
        }
        
        if ($this->performance_score >= 9) {
            return '<span class="badge bg-success">ممتاز</span>';
        } elseif ($this->performance_score >= 7) {
            return '<span class="badge bg-primary">جيد جداً</span>';
        } elseif ($this->performance_score >= 5) {
            return '<span class="badge bg-warning">جيد</span>';
        } else {
            return '<span class="badge bg-danger">ضعيف</span>';
        }
    }

    public function getHireDateFormatted(): string
    {
        if (!$this->hire_date) {
            return 'غير محدد';
        }
        
        return $this->hire_date->format('Y-m-d');
    }

    public function getTerminationDateFormatted(): string
    {
        if (!$this->termination_date) {
            return 'غير محدد';
        }
        
        return $this->termination_date->format('Y-m-d');
    }

    public function getTenureDays(): int
    {
        if (!$this->hire_date) {
            return 0;
        }
        
        $endDate = $this->termination_date ?? now();
        return $this->hire_date->diffInDays($endDate);
    }

    public function getTenureFormatted(): string
    {
        $days = $this->getTenureDays();
        
        if ($days == 0) {
            return 'اليوم الأول';
        }
        
        if ($days < 30) {
            return $days . ' يوم';
        }
        
        $months = floor($days / 30);
        $remainingDays = $days % 30;
        
        if ($months < 12) {
            $result = $months . ' شهر';
            if ($remainingDays > 0) {
                $result .= ' و' . $remainingDays . ' يوم';
            }
            return $result;
        }
        
        $years = floor($months / 12);
        $remainingMonths = $months % 12;
        
        $result = $years . ' سنة';
        if ($remainingMonths > 0) {
            $result .= ' و' . $remainingMonths . ' شهر';
        }
        
        return $result;
    }

    // Business Logic Methods
    public function isAvailable(): bool
    {
        return $this->is_available && $this->status === 'active';
    }

    public function canAcceptOrders(): bool
    {
        return $this->isAvailable() && $this->is_verified;
    }

    public function updateLocation(float $lat, float $lng): void
    {
        $this->update([
            'latitude' => $lat,
            'longitude' => $lng,
            'current_location' => [
                'lat' => $lat,
                'lng' => $lng,
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }

    public function setAvailability(bool $available): void
    {
        $this->update(['is_available' => $available]);
    }

    public function addSkill(string $skill): void
    {
        $skills = $this->skills ?? [];
        if (!in_array($skill, $skills)) {
            $skills[] = $skill;
            $this->update(['skills' => $skills]);
        }
    }

    public function removeSkill(string $skill): void
    {
        $skills = $this->skills ?? [];
        $skills = array_filter($skills, fn($s) => $s !== $skill);
        $this->update(['skills' => array_values($skills)]);
    }

    public function addCertification(string $certification): void
    {
        $certifications = $this->certifications ?? [];
        if (!in_array($certification, $certifications)) {
            $certifications[] = $certification;
            $this->update(['certifications' => $certifications]);
        }
    }

    public function removeCertification(string $certification): void
    {
        $certifications = $this->certifications ?? [];
        $certifications = array_filter($certifications, fn($c) => $c !== $certification);
        $this->update(['certifications' => array_values($certifications)]);
    }

    public function updateRating(): void
    {
        $reviews = $this->reviews()->whereNotNull('rating');
        
        if ($reviews->count() > 0) {
            $averageRating = $reviews->avg('rating');
            $this->update(['rating' => round($averageRating, 2)]);
        }
    }

    public function incrementOrders(): void
    {
        $this->increment('total_orders');
    }

    public function addEarnings(float $amount): void
    {
        $this->increment('total_earnings', $amount);
    }

    public function calculateCommission(float $orderAmount): float
    {
        return ($orderAmount * $this->commission_rate) / 100;
    }

    public function getAverageOrderValue(): float
    {
        if ($this->total_orders == 0) {
            return 0;
        }
        
        return $this->total_earnings / $this->total_orders;
    }

    public function getAverageOrderValueFormatted(): string
    {
        return number_format($this->getAverageOrderValue(), 2) . ' ريال';
    }

    public function getMonthlyEarnings(int $month = null, int $year = null): float
    {
        if (!$month) $month = now()->month;
        if (!$year) $year = now()->year;
        
        return $this->earnings()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('amount');
    }

    public function getMonthlyEarningsFormatted(int $month = null, int $year = null): string
    {
        return number_format($this->getMonthlyEarnings($month, $year), 2) . ' ريال';
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
        static::creating(function ($technician) {
            // Set default values
            if (is_null($technician->status)) {
                $technician->status = 'active';
            }
            
            if (is_null($technician->is_available)) {
                $technician->is_available = true;
            }
            
            if (is_null($technician->is_verified)) {
                $technician->is_verified = false;
            }
            
            if (is_null($technician->total_orders)) {
                $technician->total_orders = 0;
            }
            
            if (is_null($technician->total_earnings)) {
                $technician->total_earnings = 0;
            }
            
            if (is_null($technician->commission_rate)) {
                $technician->commission_rate = 15.00; // Default 15%
            }
            
            if (is_null($technician->rating)) {
                $technician->rating = 0;
            }
        });

        static::created(function ($technician) {
            // Clear cache
            Cache::forget('available_technicians_count');
            Cache::forget('verified_technicians_count');
        });

        static::updated(function ($technician) {
            // Clear cache
            Cache::forget('available_technicians_count');
            Cache::forget('verified_technicians_count');
        });

        static::deleted(function ($technician) {
            // Clear cache
            Cache::forget('available_technicians_count');
            Cache::forget('verified_technicians_count');
        });
    }
} 