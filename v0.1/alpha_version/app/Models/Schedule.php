<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'break_start_time',
        'break_end_time',
        'break_duration',
        'total_working_hours',
        'status',
        'type',
        'notes',
        'notes_en',
        'location',
        'location_en',
        'latitude',
        'longitude',
        'is_available',
        'is_booked',
        'max_appointments',
        'current_appointments',
        'available_slots',
        'metadata',
    ];

    protected $hidden = [
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'break_start_time' => 'datetime',
        'break_end_time' => 'datetime',
        'duration' => 'integer',
        'break_duration' => 'integer',
        'total_working_hours' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_available' => 'boolean',
        'is_booked' => 'boolean',
        'max_appointments' => 'integer',
        'current_appointments' => 'integer',
        'available_slots' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status
    const STATUS_AVAILABLE = 'available';
    const STATUS_BOOKED = 'booked';
    const STATUS_BUSY = 'busy';
    const STATUS_OFF = 'off';
    const STATUS_HOLIDAY = 'holiday';
    const STATUS_SICK = 'sick';
    const STATUS_MAINTENANCE = 'maintenance';

    // Types
    const TYPE_REGULAR = 'regular';
    const TYPE_OVERTIME = 'overtime';
    const TYPE_EMERGENCY = 'emergency';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_TRAINING = 'training';
    const TYPE_MEETING = 'meeting';

    // Relationships
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Scopes
    public function scopeByTechnician(Builder $query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeByDate(Builder $query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeToday(Builder $query)
    {
        return $query->where('date', Carbon::today());
    }

    public function scopeTomorrow(Builder $query)
    {
        return $query->where('date', Carbon::tomorrow());
    }

    public function scopeThisWeek(Builder $query)
    {
        return $query->whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    }

    public function scopeNextWeek(Builder $query)
    {
        return $query->whereBetween('date', [
            Carbon::now()->addWeek()->startOfWeek(),
            Carbon::now()->addWeek()->endOfWeek()
        ]);
    }

    public function scopeThisMonth(Builder $query)
    {
        return $query->whereBetween('date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
    }

    public function scopeNextMonth(Builder $query)
    {
        return $query->whereBetween('date', [
            Carbon::now()->addMonth()->startOfMonth(),
            Carbon::now()->addMonth()->endOfMonth()
        ]);
    }

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAvailable(Builder $query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeBooked(Builder $query)
    {
        return $query->where('status', self::STATUS_BOOKED);
    }

    public function scopeBusy(Builder $query)
    {
        return $query->where('status', self::STATUS_BUSY);
    }

    public function scopeOff(Builder $query)
    {
        return $query->where('status', self::STATUS_OFF);
    }

    public function scopeHoliday(Builder $query)
    {
        return $query->where('status', self::STATUS_HOLIDAY);
    }

    public function scopeSick(Builder $query)
    {
        return $query->where('status', self::STATUS_SICK);
    }

    public function scopeMaintenance(Builder $query)
    {
        return $query->where('status', self::STATUS_MAINTENANCE);
    }

    public function scopeByType(Builder $query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRegular(Builder $query)
    {
        return $query->where('type', self::TYPE_REGULAR);
    }

    public function scopeOvertime(Builder $query)
    {
        return $query->where('type', self::TYPE_OVERTIME);
    }

    public function scopeEmergency(Builder $query)
    {
        return $query->where('type', self::TYPE_EMERGENCY);
    }

    public function scopeTraining(Builder $query)
    {
        return $query->where('type', self::TYPE_TRAINING);
    }

    public function scopeMeeting(Builder $query)
    {
        return $query->where('type', self::TYPE_MEETING);
    }

    public function scopeAvailableForBooking(Builder $query)
    {
        return $query->where('is_available', true)
            ->where('is_booked', false)
            ->where('available_slots', '>', 0);
    }

    public function scopeFullyBooked(Builder $query)
    {
        return $query->where('is_booked', true)
            ->orWhere('available_slots', '<=', 0);
    }

    public function scopePartiallyBooked(Builder $query)
    {
        return $query->where('is_booked', true)
            ->where('available_slots', '>', 0);
    }

    public function scopeByTimeRange(Builder $query, $startTime, $endTime)
    {
        return $query->where('start_time', '>=', $startTime)
            ->where('end_time', '<=', $endTime);
    }

    public function scopeByWorkingHours(Builder $query, $minHours = null, $maxHours = null)
    {
        if ($minHours) {
            $query->where('total_working_hours', '>=', $minHours);
        }
        
        if ($maxHours) {
            $query->where('total_working_hours', '<=', $maxHours);
        }
        
        return $query;
    }

    public function scopeByLocation(Builder $query, $latitude, $longitude, $radius = 10)
    {
        return $query->whereRaw("
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?
        ", [$latitude, $longitude, $latitude, $radius]);
    }

    public function scopeUpcoming(Builder $query, $days = 7)
    {
        return $query->where('date', '>=', Carbon::today())
            ->where('date', '<=', Carbon::today()->addDays($days));
    }

    public function scopePast(Builder $query, $days = 30)
    {
        return $query->where('date', '<', Carbon::today())
            ->where('date', '>=', Carbon::today()->subDays($days));
    }

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // Helper Methods
    public function getNotesAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->notes) {
            return $this->notes;
        }
        
        return $value ?: $this->notes_en;
    }

    public function getLocationAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->location) {
            return $this->location;
        }
        
        return $value ?: $this->location_en;
    }

    public function getNotesArAttribute()
    {
        return $this->notes;
    }

    public function getLocationArAttribute()
    {
        return $this->location;
    }

    public function getNotesEnAttribute()
    {
        return $this->notes_en;
    }

    public function getLocationEnAttribute()
    {
        return $this->location_en;
    }

    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case self::STATUS_AVAILABLE:
                return 'Available';
            case self::STATUS_BOOKED:
                return 'Booked';
            case self::STATUS_BUSY:
                return 'Busy';
            case self::STATUS_OFF:
                return 'Off';
            case self::STATUS_HOLIDAY:
                return 'Holiday';
            case self::STATUS_SICK:
                return 'Sick';
            case self::STATUS_MAINTENANCE:
                return 'Maintenance';
            default:
                return ucfirst($this->status);
        }
    }

    public function getStatusNameArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_AVAILABLE:
                return 'متاح';
            case self::STATUS_BOOKED:
                return 'محجوز';
            case self::STATUS_BUSY:
                return 'مشغول';
            case self::STATUS_OFF:
                return 'إجازة';
            case self::STATUS_HOLIDAY:
                return 'عطلة';
            case self::STATUS_SICK:
                return 'مرضي';
            case self::STATUS_MAINTENANCE:
                return 'صيانة';
            default:
                return ucfirst($this->status);
        }
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case self::STATUS_AVAILABLE:
                return '<span class="badge bg-success">Available</span>';
            case self::STATUS_BOOKED:
                return '<span class="badge bg-warning">Booked</span>';
            case self::STATUS_BUSY:
                return '<span class="badge bg-danger">Busy</span>';
            case self::STATUS_OFF:
                return '<span class="badge bg-secondary">Off</span>';
            case self::STATUS_HOLIDAY:
                return '<span class="badge bg-info">Holiday</span>';
            case self::STATUS_SICK:
                return '<span class="badge bg-danger">Sick</span>';
            case self::STATUS_MAINTENANCE:
                return '<span class="badge bg-warning">Maintenance</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    public function getStatusBadgeArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_AVAILABLE:
                return '<span class="badge bg-success">متاح</span>';
            case self::STATUS_BOOKED:
                return '<span class="badge bg-warning">محجوز</span>';
            case self::STATUS_BUSY:
                return '<span class="badge bg-danger">مشغول</span>';
            case self::STATUS_OFF:
                return '<span class="badge bg-secondary">إجازة</span>';
            case self::STATUS_HOLIDAY:
                return '<span class="badge bg-info">عطلة</span>';
            case self::STATUS_SICK:
                return '<span class="badge bg-danger">مرضي</span>';
            case self::STATUS_MAINTENANCE:
                return '<span class="badge bg-warning">صيانة</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getTypeNameAttribute()
    {
        switch ($this->type) {
            case self::TYPE_REGULAR:
                return 'Regular';
            case self::TYPE_OVERTIME:
                return 'Overtime';
            case self::TYPE_EMERGENCY:
                return 'Emergency';
            case self::TYPE_MAINTENANCE:
                return 'Maintenance';
            case self::TYPE_TRAINING:
                return 'Training';
            case self::TYPE_MEETING:
                return 'Meeting';
            default:
                return ucfirst($this->type);
        }
    }

    public function getTypeNameArAttribute()
    {
        switch ($this->type) {
            case self::TYPE_REGULAR:
                return 'عادي';
            case self::TYPE_OVERTIME:
                return 'إضافي';
            case self::TYPE_EMERGENCY:
                return 'طوارئ';
            case self::TYPE_MAINTENANCE:
                return 'صيانة';
            case self::TYPE_TRAINING:
                return 'تدريب';
            case self::TYPE_MEETING:
                return 'اجتماع';
            default:
                return ucfirst($this->type);
        }
    }

    public function getTypeBadgeAttribute()
    {
        switch ($this->type) {
            case self::TYPE_REGULAR:
                return '<span class="badge bg-primary">Regular</span>';
            case self::TYPE_OVERTIME:
                return '<span class="badge bg-warning">Overtime</span>';
            case self::TYPE_EMERGENCY:
                return '<span class="badge bg-danger">Emergency</span>';
            case self::TYPE_MAINTENANCE:
                return '<span class="badge bg-info">Maintenance</span>';
            case self::TYPE_TRAINING:
                return '<span class="badge bg-success">Training</span>';
            case self::TYPE_MEETING:
                return '<span class="badge bg-secondary">Meeting</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    public function getTypeBadgeArAttribute()
    {
        switch ($this->type) {
            case self::TYPE_REGULAR:
                return '<span class="badge bg-primary">عادي</span>';
            case self::TYPE_OVERTIME:
                return '<span class="badge bg-warning">إضافي</span>';
            case self::TYPE_EMERGENCY:
                return '<span class="badge bg-danger">طوارئ</span>';
            case self::TYPE_MAINTENANCE:
                return '<span class="badge bg-info">صيانة</span>';
            case self::TYPE_TRAINING:
                return '<span class="badge bg-success">تدريب</span>';
            case self::TYPE_MEETING:
                return '<span class="badge bg-secondary">اجتماع</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getDateFormattedAttribute()
    {
        return $this->date->format('M d, Y');
    }

    public function getDateFormattedArAttribute()
    {
        return $this->date->format('d M Y');
    }

    public function getStartTimeFormattedAttribute()
    {
        return $this->start_time ? $this->start_time->format('H:i') : null;
    }

    public function getStartTimeFormattedArAttribute()
    {
        return $this->start_time ? $this->start_time->format('H:i') : null;
    }

    public function getEndTimeFormattedAttribute()
    {
        return $this->end_time ? $this->end_time->format('H:i') : null;
    }

    public function getEndTimeFormattedArAttribute()
    {
        return $this->end_time ? $this->end_time->format('H:i') : null;
    }

    public function getBreakStartTimeFormattedAttribute()
    {
        return $this->break_start_time ? $this->break_start_time->format('H:i') : null;
    }

    public function getBreakStartTimeFormattedArAttribute()
    {
        return $this->break_start_time ? $this->break_start_time->format('H:i') : null;
    }

    public function getBreakEndTimeFormattedAttribute()
    {
        return $this->break_end_time ? $this->break_end_time->format('H:i') : null;
    }

    public function getBreakEndTimeFormattedArAttribute()
    {
        return $this->break_end_time ? $this->break_end_time->format('H:i') : null;
    }

    public function getDurationFormattedAttribute()
    {
        if (!$this->duration) {
            return null;
        }
        
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } elseif ($hours > 0) {
            return $hours . 'h';
        } else {
            return $minutes . 'm';
        }
    }

    public function getDurationFormattedArAttribute()
    {
        if (!$this->duration) {
            return null;
        }
        
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return $hours . ' ساعة ' . $minutes . ' دقيقة';
        } elseif ($hours > 0) {
            return $hours . ' ساعة';
        } else {
            return $minutes . ' دقيقة';
        }
    }

    public function getBreakDurationFormattedAttribute()
    {
        if (!$this->break_duration) {
            return null;
        }
        
        $hours = floor($this->break_duration / 60);
        $minutes = $this->break_duration % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } elseif ($hours > 0) {
            return $hours . 'h';
        } else {
            return $minutes . 'm';
        }
    }

    public function getBreakDurationFormattedArAttribute()
    {
        if (!$this->break_duration) {
            return null;
        }
        
        $hours = floor($this->break_duration / 60);
        $minutes = $this->break_duration % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return $hours . ' ساعة ' . $minutes . ' دقيقة';
        } elseif ($hours > 0) {
            return $hours . ' ساعة';
        } else {
            return $minutes . ' دقيقة';
        }
    }

    public function getTotalWorkingHoursFormattedAttribute()
    {
        if (!$this->total_working_hours) {
            return null;
        }
        
        return number_format($this->total_working_hours, 1) . ' hours';
    }

    public function getTotalWorkingHoursFormattedArAttribute()
    {
        if (!$this->total_working_hours) {
            return null;
        }
        
        return number_format($this->total_working_hours, 1) . ' ساعة';
    }

    public function getBookingPercentageAttribute()
    {
        if (!$this->max_appointments) {
            return 0;
        }
        
        return round(($this->current_appointments / $this->max_appointments) * 100, 2);
    }

    public function getBookingPercentageFormattedAttribute()
    {
        return $this->getBookingPercentageAttribute() . '%';
    }

    public function getBookingPercentageFormattedArAttribute()
    {
        return $this->getBookingPercentageAttribute() . '%';
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
    public function isAvailable()
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isBooked()
    {
        return $this->status === self::STATUS_BOOKED;
    }

    public function isBusy()
    {
        return $this->status === self::STATUS_BUSY;
    }

    public function isOff()
    {
        return $this->status === self::STATUS_OFF;
    }

    public function isHoliday()
    {
        return $this->status === self::STATUS_HOLIDAY;
    }

    public function isSick()
    {
        return $this->status === self::STATUS_SICK;
    }

    public function isMaintenance()
    {
        return $this->status === self::STATUS_MAINTENANCE;
    }

    public function isRegular()
    {
        return $this->type === self::TYPE_REGULAR;
    }

    public function isOvertime()
    {
        return $this->type === self::TYPE_OVERTIME;
    }

    public function isEmergency()
    {
        return $this->type === self::TYPE_EMERGENCY;
    }

    public function isTraining()
    {
        return $this->type === self::TYPE_TRAINING;
    }

    public function isMeeting()
    {
        return $this->type === self::TYPE_MEETING;
    }

    public function isToday()
    {
        return $this->date->isToday();
    }

    public function isTomorrow()
    {
        return $this->date->isTomorrow();
    }

    public function isPast()
    {
        return $this->date->isPast();
    }

    public function isFuture()
    {
        return $this->date->isFuture();
    }

    public function isThisWeek()
    {
        return $this->date->isCurrentWeek();
    }

    public function isNextWeek()
    {
        return $this->date->isNextWeek();
    }

    public function isThisMonth()
    {
        return $this->date->isCurrentMonth();
    }

    public function isNextMonth()
    {
        return $this->date->isNextMonth();
    }

    public function hasBreak()
    {
        return $this->break_start_time && $this->break_end_time;
    }

    public function hasLocation()
    {
        return !empty($this->location) || !empty($this->location_en);
    }

    public function hasCoordinates()
    {
        return $this->latitude && $this->longitude;
    }

    public function canBeBooked()
    {
        return $this->isAvailable() && $this->available_slots > 0;
    }

    public function isFullyBooked()
    {
        return $this->available_slots <= 0;
    }

    public function isPartiallyBooked()
    {
        return $this->current_appointments > 0 && $this->available_slots > 0;
    }

    public function canBeEdited()
    {
        return $this->isFuture() || $this->isToday();
    }

    public function canBeDeleted()
    {
        return $this->isFuture();
    }

    public function setAvailable()
    {
        $this->update([
            'status' => self::STATUS_AVAILABLE,
            'is_available' => true,
            'is_booked' => false,
        ]);
        $this->clearCache();
    }

    public function setBooked()
    {
        $this->update([
            'status' => self::STATUS_BOOKED,
            'is_available' => false,
            'is_booked' => true,
        ]);
        $this->clearCache();
    }

    public function setBusy()
    {
        $this->update([
            'status' => self::STATUS_BUSY,
            'is_available' => false,
            'is_booked' => true,
        ]);
        $this->clearCache();
    }

    public function setOff()
    {
        $this->update([
            'status' => self::STATUS_OFF,
            'is_available' => false,
            'is_booked' => false,
        ]);
        $this->clearCache();
    }

    public function setHoliday()
    {
        $this->update([
            'status' => self::STATUS_HOLIDAY,
            'is_available' => false,
            'is_booked' => false,
        ]);
        $this->clearCache();
    }

    public function setSick()
    {
        $this->update([
            'status' => self::STATUS_SICK,
            'is_available' => false,
            'is_booked' => false,
        ]);
        $this->clearCache();
    }

    public function setMaintenance()
    {
        $this->update([
            'status' => self::STATUS_MAINTENANCE,
            'is_available' => false,
            'is_booked' => false,
        ]);
        $this->clearCache();
    }

    public function bookAppointment()
    {
        if ($this->available_slots > 0) {
            $this->update([
                'current_appointments' => $this->current_appointments + 1,
                'available_slots' => $this->available_slots - 1,
            ]);
            
            if ($this->available_slots <= 0) {
                $this->setBooked();
            }
            
            $this->clearCache();
            return true;
        }
        
        return false;
    }

    public function cancelAppointment()
    {
        if ($this->current_appointments > 0) {
            $this->update([
                'current_appointments' => $this->current_appointments - 1,
                'available_slots' => $this->available_slots + 1,
            ]);
            
            if ($this->status === self::STATUS_BOOKED && $this->available_slots > 0) {
                $this->setAvailable();
            }
            
            $this->clearCache();
            return true;
        }
        
        return false;
    }

    public function updateWorkingHours()
    {
        if ($this->start_time && $this->end_time) {
            $duration = $this->start_time->diffInMinutes($this->end_time);
            
            if ($this->hasBreak()) {
                $breakDuration = $this->break_start_time->diffInMinutes($this->break_end_time);
                $duration -= $breakDuration;
            }
            
            $this->update([
                'duration' => $duration,
                'total_working_hours' => round($duration / 60, 2),
            ]);
            
            $this->clearCache();
        }
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->current_appointments = 0;
        $duplicate->available_slots = $this->max_appointments;
        $duplicate->is_available = true;
        $duplicate->is_booked = false;
        $duplicate->save();
        
        return $duplicate;
    }

    public function getAvailableTimeSlots($slotDuration = 60)
    {
        if (!$this->canBeBooked()) {
            return [];
        }
        
        $slots = [];
        $currentTime = $this->start_time->copy();
        $endTime = $this->end_time->copy();
        
        while ($currentTime < $endTime) {
            $slotEnd = $currentTime->copy()->addMinutes($slotDuration);
            
            if ($slotEnd <= $endTime) {
                // Check if slot overlaps with break
                if ($this->hasBreak()) {
                    $breakStart = $this->break_start_time;
                    $breakEnd = $this->break_end_time;
                    
                    if (!($currentTime >= $breakEnd || $slotEnd <= $breakStart)) {
                        $currentTime = $slotEnd;
                        continue;
                    }
                }
                
                $slots[] = [
                    'start' => $currentTime->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'available' => true,
                ];
            }
            
            $currentTime = $slotEnd;
        }
        
        return $slots;
    }

    public function getTotalRevenue()
    {
        return Cache::remember("schedule_revenue_{$this->id}", 3600, function () {
            return $this->appointments()->sum('actual_cost');
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

    public function getAverageAppointmentValue()
    {
        return Cache::remember("schedule_avg_appointment_{$this->id}", 3600, function () {
            return $this->appointments()->avg('actual_cost') ?: 0;
        });
    }

    public function getAverageAppointmentValueFormatted()
    {
        return number_format($this->getAverageAppointmentValue(), 2) . ' SAR';
    }

    public function getAverageAppointmentValueFormattedAr()
    {
        return number_format($this->getAverageAppointmentValue(), 2) . ' ريال';
    }

    // Static Methods
    public static function getSchedulesCountForTechnician($technicianId)
    {
        return Cache::remember("technician_schedules_count_{$technicianId}", 3600, function () use ($technicianId) {
            return static::where('technician_id', $technicianId)->count();
        });
    }

    public static function getAvailableSchedulesCount()
    {
        return Cache::remember('available_schedules_count', 3600, function () {
            return static::where('is_available', true)->count();
        });
    }

    public static function getBookedSchedulesCount()
    {
        return Cache::remember('booked_schedules_count', 3600, function () {
            return static::where('is_booked', true)->count();
        });
    }

    public static function getSchedulesForTechnician($technicianId, $startDate = null, $endDate = null)
    {
        $query = static::where('technician_id', $technicianId);
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        return $query->orderBy('date')->orderBy('start_time')->get();
    }

    public static function getAvailableSchedules($date = null)
    {
        $query = static::where('is_available', true)
            ->where('available_slots', '>', 0);
        
        if ($date) {
            $query->where('date', $date);
        }
        
        return $query->orderBy('date')->orderBy('start_time')->get();
    }

    public static function getTodaySchedules()
    {
        return static::where('date', Carbon::today())
            ->with(['technician'])
            ->orderBy('start_time')
            ->get();
    }

    public static function getTomorrowSchedules()
    {
        return static::where('date', Carbon::tomorrow())
            ->with(['technician'])
            ->orderBy('start_time')
            ->get();
    }

    public static function getThisWeekSchedules()
    {
        return static::whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->with(['technician'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    public static function getNextWeekSchedules()
    {
        return static::whereBetween('date', [
            Carbon::now()->addWeek()->startOfWeek(),
            Carbon::now()->addWeek()->endOfWeek()
        ])
            ->with(['technician'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    public static function getSchedulesByStatus($status, $limit = 50)
    {
        return static::where('status', $status)
            ->with(['technician'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }

    public static function getSchedulesByType($type, $limit = 50)
    {
        return static::where('type', $type)
            ->with(['technician'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }

    public static function getSchedulesStats($technicianId = null)
    {
        $query = static::query();
        
        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }

        return Cache::remember("schedules_stats" . ($technicianId ? "_{$technicianId}" : ""), 3600, function () use ($query) {
            return [
                'total' => $query->count(),
                'available' => $query->where('status', self::STATUS_AVAILABLE)->count(),
                'booked' => $query->where('status', self::STATUS_BOOKED)->count(),
                'busy' => $query->where('status', self::STATUS_BUSY)->count(),
                'off' => $query->where('status', self::STATUS_OFF)->count(),
                'holiday' => $query->where('status', self::STATUS_HOLIDAY)->count(),
                'sick' => $query->where('status', self::STATUS_SICK)->count(),
                'maintenance' => $query->where('status', self::STATUS_MAINTENANCE)->count(),
                'regular' => $query->where('type', self::TYPE_REGULAR)->count(),
                'overtime' => $query->where('type', self::TYPE_OVERTIME)->count(),
                'emergency' => $query->where('type', self::TYPE_EMERGENCY)->count(),
                'training' => $query->where('type', self::TYPE_TRAINING)->count(),
                'meeting' => $query->where('type', self::TYPE_MEETING)->count(),
                'today' => $query->where('date', Carbon::today())->count(),
                'tomorrow' => $query->where('date', Carbon::tomorrow())->count(),
                'this_week' => $query->whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count(),
                'next_week' => $query->whereBetween('date', [Carbon::now()->addWeek()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()])->count(),
                'total_working_hours' => $query->sum('total_working_hours'),
                'total_appointments' => $query->sum('current_appointments'),
                'total_available_slots' => $query->sum('available_slots'),
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        $technicianId = $this->technician_id;
        
        Cache::forget("technician_schedules_count_{$technicianId}");
        Cache::forget('available_schedules_count');
        Cache::forget('booked_schedules_count');
        Cache::forget("schedules_stats_{$technicianId}");
        Cache::forget("schedules_stats");
        
        Cache::forget("schedule_revenue_{$this->id}");
        Cache::forget("schedule_avg_appointment_{$this->id}");
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($schedule) {
            if (!isset($schedule->is_available)) {
                $schedule->is_available = true;
            }
            
            if (!isset($schedule->is_booked)) {
                $schedule->is_booked = false;
            }
            
            if (!$schedule->current_appointments) {
                $schedule->current_appointments = 0;
            }
            
            if (!$schedule->available_slots && $schedule->max_appointments) {
                $schedule->available_slots = $schedule->max_appointments;
            }
            
            if ($schedule->start_time && $schedule->end_time) {
                $schedule->updateWorkingHours();
            }
        });

        static::created(function ($schedule) {
            $schedule->clearCache();
        });

        static::updated(function ($schedule) {
            $schedule->clearCache();
        });

        static::deleted(function ($schedule) {
            $schedule->clearCache();
        });
    }
}