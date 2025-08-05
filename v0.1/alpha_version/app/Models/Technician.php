<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Technician extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specializations',
        'certifications',
        'experience_years',
        'hourly_rate',
        'is_available',
        'is_verified',
        'is_active',
        'rating_average',
        'rating_count',
        'total_appointments',
        'total_earnings',
        'commission_rate',
        'working_hours',
        'service_areas',
        'payment_info',
        'documents',
        'bio',
        'bio_en',
        'meta_data',
    ];

    protected $casts = [
        'specializations' => 'array',
        'certifications' => 'array',
        'experience_years' => 'integer',
        'hourly_rate' => 'decimal:2',
        'is_available' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'rating_average' => 'decimal:1',
        'rating_count' => 'integer',
        'total_appointments' => 'integer',
        'total_earnings' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'working_hours' => 'array',
        'service_areas' => 'array',
        'payment_info' => 'array',
        'documents' => 'array',
        'meta_data' => 'array',
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

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewable_id')
            ->where('reviewable_type', Technician::class);
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

    public function scopeBySpecialization($query, $specialization)
    {
        return $query->whereJsonContains('specializations', $specialization);
    }

    public function scopeByExperience($query, $minYears)
    {
        return $query->where('experience_years', '>=', $minYears);
    }

    public function scopeByHourlyRate($query, $maxRate)
    {
        return $query->where('hourly_rate', '<=', $maxRate);
    }

    // Helper Methods
    public function getAverageRating(): float
    {
        return $this->rating_average ?? 0;
    }

    public function getTotalAppointments(): int
    {
        return $this->total_appointments ?? 0;
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

    public function getSpecializationsList(): string
    {
        if (empty($this->specializations)) {
            return 'غير محدد';
        }
        
        return implode(', ', $this->specializations);
    }

    public function offersHomeService(): bool
    {
        return in_array('home_service', $this->specializations ?? []);
    }

    public function offersEmergencyService(): bool
    {
        return in_array('emergency_service', $this->specializations ?? []);
    }

    public function getLocalizedBio(): string
    {
        return app()->getLocale() === 'ar' ? $this->bio : $this->bio_en;
    }

    public function getFormattedHourlyRate(): string
    {
        return number_format($this->hourly_rate, 2) . ' SAR/ساعة';
    }

    public function getExperienceText(): string
    {
        $years = $this->experience_years;
        
        if ($years == 1) {
            return 'سنة واحدة';
        } elseif ($years == 2) {
            return 'سنتان';
        } elseif ($years >= 3 && $years <= 10) {
            return $years . ' سنوات';
        } else {
            return $years . ' سنة';
        }
    }

    public function getCurrentAppointments(): int
    {
        return $this->appointments()
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->count();
    }

    public function getCompletedAppointmentsToday(): int
    {
        return $this->appointments()
            ->where('status', 'completed')
            ->whereDate('appointment_date', today())
            ->count();
    }

    public function updateStats(): void
    {
        $this->update([
            'total_appointments' => $this->appointments()->where('status', 'completed')->count(),
            'total_earnings' => $this->appointments()
                ->where('status', 'completed')
                ->sum('total_amount'),
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

    public function getWorkingHoursText(): string
    {
        if (empty($this->working_hours)) {
            return 'غير محدد';
        }
        
        $hours = [];
        foreach ($this->working_hours as $day => $time) {
            $hours[] = $day . ': ' . $time;
        }
        
        return implode(', ', $hours);
    }

    public function isWorkingNow(): bool
    {
        if (empty($this->working_hours)) {
            return true;
        }
        
        $today = strtolower(now()->format('l'));
        $currentTime = now()->format('H:i');
        
        if (!isset($this->working_hours[$today])) {
            return false;
        }
        
        $hours = $this->working_hours[$today];
        if ($hours === 'closed') {
            return false;
        }
        
        list($open, $close) = explode('-', $hours);
        return $currentTime >= $open && $currentTime <= $close;
    }

    public function getCertificationsList(): string
    {
        if (empty($this->certifications)) {
            return 'غير محدد';
        }
        
        return implode(', ', $this->certifications);
    }

    public function hasCertification(string $certification): bool
    {
        return in_array($certification, $this->certifications ?? []);
    }

    public function getServiceAreasText(): string
    {
        if (empty($this->service_areas)) {
            return 'جميع المناطق';
        }
        
        return implode(', ', $this->service_areas);
    }
} 