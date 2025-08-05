<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'technician_id',
        'service_type',
        'appointment_date',
        'appointment_time',
        'duration',
        'status',
        'address',
        'latitude',
        'longitude',
        'description',
        'notes',
        'estimated_cost',
        'actual_cost',
        'payment_status',
        'cancellation_reason',
        'cancelled_at',
        'completed_at',
        'rating',
        'review',
        'metadata',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime',
        'duration' => 'integer',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'rating' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['cancelled', 'completed']);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('appointment_date', '>=', now()->toDateString())
                    ->where('status', 'confirmed');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->where('appointment_date', now()->toDateString());
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByServiceType(Builder $query, string $serviceType): Builder
    {
        return $query->where('service_type', $serviceType);
    }

    public function scopeByTechnician(Builder $query, int $technicianId): Builder
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('appointment_date', [$startDate, $endDate]);
    }

    // Helper Methods
    public function getFullDateTime(): string
    {
        return $this->appointment_date->format('Y-m-d') . ' ' . $this->appointment_time->format('H:i');
    }

    public function getFormattedDate(): string
    {
        return $this->appointment_date->format('Y-m-d');
    }

    public function getFormattedTime(): string
    {
        return $this->appointment_time->format('H:i');
    }

    public function getDurationFormatted(): string
    {
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        
        if ($hours > 0) {
            return $hours . ' ساعة ' . ($minutes > 0 ? $minutes . ' دقيقة' : '');
        }
        
        return $minutes . ' دقيقة';
    }

    public function getStatusBadge(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">في الانتظار</span>',
            'confirmed' => '<span class="badge bg-primary">مؤكد</span>',
            'in_progress' => '<span class="badge bg-info">قيد التنفيذ</span>',
            'completed' => '<span class="badge bg-success">مكتمل</span>',
            'cancelled' => '<span class="badge bg-danger">ملغي</span>',
            'no_show' => '<span class="badge bg-secondary">لم يحضر</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge bg-secondary">' . $this->status . '</span>';
    }

    public function getPaymentStatusBadge(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">في الانتظار</span>',
            'paid' => '<span class="badge bg-success">مدفوع</span>',
            'partial' => '<span class="badge bg-info">مدفوع جزئياً</span>',
            'refunded' => '<span class="badge bg-secondary">مسترد</span>',
        ];
        
        return $badges[$this->payment_status] ?? '<span class="badge bg-secondary">' . $this->payment_status . '</span>';
    }

    public function getServiceTypeName(): string
    {
        $services = [
            'engine_maintenance' => 'صيانة المحرك',
            'brake_service' => 'خدمة الفرامل',
            'oil_change' => 'تغيير الزيت',
            'tire_service' => 'خدمة الإطارات',
            'electrical_service' => 'خدمة الكهرباء',
            'ac_service' => 'خدمة التكييف',
            'diagnostic' => 'تشخيص الأعطال',
            'emergency_service' => 'خدمة طوارئ',
        ];
        
        return $services[$this->service_type] ?? $this->service_type;
    }

    public function getServiceTypeIcon(): string
    {
        $icons = [
            'engine_maintenance' => 'fas fa-cog',
            'brake_service' => 'fas fa-stop-circle',
            'oil_change' => 'fas fa-oil-can',
            'tire_service' => 'fas fa-circle',
            'electrical_service' => 'fas fa-bolt',
            'ac_service' => 'fas fa-snowflake',
            'diagnostic' => 'fas fa-search',
            'emergency_service' => 'fas fa-exclamation-triangle',
        ];
        
        return $icons[$this->service_type] ?? 'fas fa-tools';
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

    public function isUpcoming(): bool
    {
        $appointmentDateTime = $this->appointment_date->setTimeFrom($this->appointment_time);
        return $appointmentDateTime->isFuture();
    }

    public function isToday(): bool
    {
        return $this->appointment_date->isToday();
    }

    public function isOverdue(): bool
    {
        $appointmentDateTime = $this->appointment_date->setTimeFrom($this->appointment_time);
        return $appointmentDateTime->isPast() && $this->status === 'confirmed';
    }

    public function getTimeUntilAppointment(): ?string
    {
        if (!$this->isUpcoming()) {
            return null;
        }
        
        $appointmentDateTime = $this->appointment_date->setTimeFrom($this->appointment_time);
        return now()->diffForHumans($appointmentDateTime, ['parts' => 2]);
    }

    public function getEstimatedCostFormatted(): string
    {
        return number_format($this->estimated_cost, 2) . ' ريال';
    }

    public function getActualCostFormatted(): string
    {
        return number_format($this->actual_cost, 2) . ' ريال';
    }

    public function getCostDifference(): float
    {
        return $this->actual_cost - $this->estimated_cost;
    }

    public function getCostDifferenceFormatted(): string
    {
        $difference = $this->getCostDifference();
        $sign = $difference >= 0 ? '+' : '';
        return $sign . number_format($difference, 2) . ' ريال';
    }

    public function getRatingStars(): string
    {
        if (!$this->rating) {
            return '<span class="text-muted">لا توجد تقييم</span>';
        }
        
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $stars .= '<i class="fas fa-star text-warning"></i>';
            } else {
                $stars .= '<i class="far fa-star text-muted"></i>';
            }
        }
        
        return $stars . ' <span class="ms-1">(' . $this->rating . '/5)</span>';
    }

    // Business Logic Methods
    public function canBeCancelled(): bool
    {
        if ($this->status === 'cancelled' || $this->status === 'completed') {
            return false;
        }
        
        $appointmentDateTime = $this->appointment_date->setTimeFrom($this->appointment_time);
        return $appointmentDateTime->diffInHours(now()) >= 2; // Can cancel up to 2 hours before
    }

    public function canBeRescheduled(): bool
    {
        if ($this->status === 'cancelled' || $this->status === 'completed') {
            return false;
        }
        
        $appointmentDateTime = $this->appointment_date->setTimeFrom($this->appointment_time);
        return $appointmentDateTime->diffInHours(now()) >= 4; // Can reschedule up to 4 hours before
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);
    }

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    public function start(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsNoShow(): void
    {
        $this->update(['status' => 'no_show']);
    }

    public function addRating(int $rating, string $review = null): void
    {
        $this->update([
            'rating' => $rating,
            'review' => $review,
        ]);
    }

    public function updatePaymentStatus(string $status): void
    {
        $this->update(['payment_status' => $status]);
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
        static::creating(function ($appointment) {
            // Set default values
            if (is_null($appointment->status)) {
                $appointment->status = 'pending';
            }
            
            if (is_null($appointment->payment_status)) {
                $appointment->payment_status = 'pending';
            }
            
            if (is_null($appointment->duration)) {
                $appointment->duration = 60; // Default 1 hour
            }
        });

        static::created(function ($appointment) {
            // Clear cache
            Cache::forget("user_appointments_count_{$appointment->user_id}");
            Cache::forget("technician_appointments_count_{$appointment->technician_id}");
        });

        static::updated(function ($appointment) {
            // Clear cache
            Cache::forget("user_appointments_count_{$appointment->user_id}");
            Cache::forget("technician_appointments_count_{$appointment->technician_id}");
        });

        static::deleted(function ($appointment) {
            // Clear cache
            Cache::forget("user_appointments_count_{$appointment->user_id}");
            Cache::forget("technician_appointments_count_{$appointment->technician_id}");
        });
    }
}