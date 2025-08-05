<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'description',
        'description_en',
        'type',
        'format',
        'status',
        'generated_by',
        'generated_at',
        'file_path',
        'file_size',
        'file_type',
        'download_count',
        'last_downloaded_at',
        'parameters',
        'filters',
        'schedule',
        'last_generated_at',
        'next_generation_at',
        'is_public',
        'is_featured',
        'expires_at',
        'notes',
        'notes_en',
        'metadata',
    ];

    protected $hidden = [
        'parameters',
        'filters',
        'schedule',
        'metadata',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'last_generated_at' => 'datetime',
        'next_generation_at' => 'datetime',
        'expires_at' => 'datetime',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'parameters' => 'array',
        'filters' => 'array',
        'schedule' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Type constants
    const TYPE_SALES = 'sales';
    const TYPE_INVENTORY = 'inventory';
    const TYPE_CUSTOMER = 'customer';
    const TYPE_PRODUCT = 'product';
    const TYPE_ORDER = 'order';
    const TYPE_PAYMENT = 'payment';
    const TYPE_SHIPMENT = 'shipment';
    const TYPE_REFUND = 'refund';
    const TYPE_REVIEW = 'review';
    const TYPE_TECHNICIAN = 'technician';
    const TYPE_SUPPLIER = 'supplier';
    const TYPE_FINANCIAL = 'financial';
    const TYPE_ANALYTICS = 'analytics';
    const TYPE_CUSTOM = 'custom';

    // Format constants
    const FORMAT_PDF = 'pdf';
    const FORMAT_EXCEL = 'excel';
    const FORMAT_CSV = 'csv';
    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';
    const FORMAT_HTML = 'html';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    // Schedule constants
    const SCHEDULE_NONE = 'none';
    const SCHEDULE_DAILY = 'daily';
    const SCHEDULE_WEEKLY = 'weekly';
    const SCHEDULE_MONTHLY = 'monthly';
    const SCHEDULE_QUARTERLY = 'quarterly';
    const SCHEDULE_YEARLY = 'yearly';

    // Relationships
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function downloads()
    {
        return $this->hasMany(ReportDownload::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Scopes
    public function scopeByType(Builder $query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByFormat(Builder $query, $format)
    {
        return $query->where('format', $format);
    }

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByGenerator(Builder $query, $generatorId)
    {
        return $query->where('generated_by', $generatorId);
    }

    public function scopeBySchedule(Builder $query, $schedule)
    {
        return $query->where('schedule->frequency', $schedule);
    }

    public function scopePublic(Builder $query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate(Builder $query)
    {
        return $query->where('is_public', false);
    }

    public function scopeFeatured(Builder $query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePending(Builder $query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing(Builder $query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeCompleted(Builder $query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed(Builder $query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeCancelled(Builder $query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeExpired(Builder $query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('generated_at', [$startDate, $endDate]);
    }

    public function scopeToday(Builder $query)
    {
        return $query->whereDate('generated_at', Carbon::today());
    }

    public function scopeThisWeek(Builder $query)
    {
        return $query->whereBetween('generated_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    }

    public function scopeThisMonth(Builder $query)
    {
        return $query->whereMonth('generated_at', Carbon::now()->month);
    }

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('generated_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeScheduled(Builder $query)
    {
        return $query->where('schedule->frequency', '!=', self::SCHEDULE_NONE);
    }

    public function scopeDueForGeneration(Builder $query)
    {
        return $query->where('next_generation_at', '<=', Carbon::now())
                    ->where('status', '!=', self::STATUS_PROCESSING);
    }

    public function scopePopular(Builder $query, $limit = 10)
    {
        return $query->orderBy('download_count', 'desc')->limit($limit);
    }

    public function scopeLargeFiles(Builder $query, $threshold = 1024 * 1024) // 1MB
    {
        return $query->where('file_size', '>', $threshold);
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

    public function getNotesEnAttribute()
    {
        return $this->notes_en;
    }

    public function getTypeNameAttribute()
    {
        switch ($this->type) {
            case self::TYPE_SALES:
                return 'Sales Report';
            case self::TYPE_INVENTORY:
                return 'Inventory Report';
            case self::TYPE_CUSTOMER:
                return 'Customer Report';
            case self::TYPE_PRODUCT:
                return 'Product Report';
            case self::TYPE_ORDER:
                return 'Order Report';
            case self::TYPE_PAYMENT:
                return 'Payment Report';
            case self::TYPE_SHIPMENT:
                return 'Shipment Report';
            case self::TYPE_REFUND:
                return 'Refund Report';
            case self::TYPE_REVIEW:
                return 'Review Report';
            case self::TYPE_TECHNICIAN:
                return 'Technician Report';
            case self::TYPE_SUPPLIER:
                return 'Supplier Report';
            case self::TYPE_FINANCIAL:
                return 'Financial Report';
            case self::TYPE_ANALYTICS:
                return 'Analytics Report';
            case self::TYPE_CUSTOM:
                return 'Custom Report';
            default:
                return ucfirst($this->type) . ' Report';
        }
    }

    public function getTypeNameArAttribute()
    {
        switch ($this->type) {
            case self::TYPE_SALES:
                return 'تقرير المبيعات';
            case self::TYPE_INVENTORY:
                return 'تقرير المخزون';
            case self::TYPE_CUSTOMER:
                return 'تقرير العملاء';
            case self::TYPE_PRODUCT:
                return 'تقرير المنتجات';
            case self::TYPE_ORDER:
                return 'تقرير الطلبات';
            case self::TYPE_PAYMENT:
                return 'تقرير المدفوعات';
            case self::TYPE_SHIPMENT:
                return 'تقرير الشحنات';
            case self::TYPE_REFUND:
                return 'تقرير الاسترداد';
            case self::TYPE_REVIEW:
                return 'تقرير التقييمات';
            case self::TYPE_TECHNICIAN:
                return 'تقرير الفنيين';
            case self::TYPE_SUPPLIER:
                return 'تقرير الموردين';
            case self::TYPE_FINANCIAL:
                return 'التقرير المالي';
            case self::TYPE_ANALYTICS:
                return 'تقرير التحليلات';
            case self::TYPE_CUSTOM:
                return 'تقرير مخصص';
            default:
                return 'تقرير ' . ucfirst($this->type);
        }
    }

    public function getTypeIconAttribute()
    {
        switch ($this->type) {
            case self::TYPE_SALES:
                return 'fas fa-chart-line';
            case self::TYPE_INVENTORY:
                return 'fas fa-boxes';
            case self::TYPE_CUSTOMER:
                return 'fas fa-users';
            case self::TYPE_PRODUCT:
                return 'fas fa-box';
            case self::TYPE_ORDER:
                return 'fas fa-shopping-cart';
            case self::TYPE_PAYMENT:
                return 'fas fa-credit-card';
            case self::TYPE_SHIPMENT:
                return 'fas fa-truck';
            case self::TYPE_REFUND:
                return 'fas fa-undo';
            case self::TYPE_REVIEW:
                return 'fas fa-star';
            case self::TYPE_TECHNICIAN:
                return 'fas fa-user-cog';
            case self::TYPE_SUPPLIER:
                return 'fas fa-industry';
            case self::TYPE_FINANCIAL:
                return 'fas fa-dollar-sign';
            case self::TYPE_ANALYTICS:
                return 'fas fa-chart-bar';
            case self::TYPE_CUSTOM:
                return 'fas fa-file-alt';
            default:
                return 'fas fa-file';
        }
    }

    public function getFormatNameAttribute()
    {
        switch ($this->format) {
            case self::FORMAT_PDF:
                return 'PDF';
            case self::FORMAT_EXCEL:
                return 'Excel';
            case self::FORMAT_CSV:
                return 'CSV';
            case self::FORMAT_JSON:
                return 'JSON';
            case self::FORMAT_XML:
                return 'XML';
            case self::FORMAT_HTML:
                return 'HTML';
            default:
                return strtoupper($this->format);
        }
    }

    public function getFormatNameArAttribute()
    {
        return $this->getFormatNameAttribute();
    }

    public function getFormatIconAttribute()
    {
        switch ($this->format) {
            case self::FORMAT_PDF:
                return 'fas fa-file-pdf';
            case self::FORMAT_EXCEL:
                return 'fas fa-file-excel';
            case self::FORMAT_CSV:
                return 'fas fa-file-csv';
            case self::FORMAT_JSON:
                return 'fas fa-file-code';
            case self::FORMAT_XML:
                return 'fas fa-file-code';
            case self::FORMAT_HTML:
                return 'fas fa-file-code';
            default:
                return 'fas fa-file';
        }
    }

    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'Pending';
            case self::STATUS_PROCESSING:
                return 'Processing';
            case self::STATUS_COMPLETED:
                return 'Completed';
            case self::STATUS_FAILED:
                return 'Failed';
            case self::STATUS_CANCELLED:
                return 'Cancelled';
            case self::STATUS_EXPIRED:
                return 'Expired';
            default:
                return 'Unknown';
        }
    }

    public function getStatusNameArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'في الانتظار';
            case self::STATUS_PROCESSING:
                return 'قيد المعالجة';
            case self::STATUS_COMPLETED:
                return 'مكتمل';
            case self::STATUS_FAILED:
                return 'فشل';
            case self::STATUS_CANCELLED:
                return 'ملغي';
            case self::STATUS_EXPIRED:
                return 'منتهي الصلاحية';
            default:
                return 'غير معروف';
        }
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return '<span class="badge bg-warning">Pending</span>';
            case self::STATUS_PROCESSING:
                return '<span class="badge bg-info">Processing</span>';
            case self::STATUS_COMPLETED:
                return '<span class="badge bg-success">Completed</span>';
            case self::STATUS_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            case self::STATUS_CANCELLED:
                return '<span class="badge bg-dark">Cancelled</span>';
            case self::STATUS_EXPIRED:
                return '<span class="badge bg-secondary">Expired</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    public function getStatusBadgeArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return '<span class="badge bg-warning">في الانتظار</span>';
            case self::STATUS_PROCESSING:
                return '<span class="badge bg-info">قيد المعالجة</span>';
            case self::STATUS_COMPLETED:
                return '<span class="badge bg-success">مكتمل</span>';
            case self::STATUS_FAILED:
                return '<span class="badge bg-danger">فشل</span>';
            case self::STATUS_CANCELLED:
                return '<span class="badge bg-dark">ملغي</span>';
            case self::STATUS_EXPIRED:
                return '<span class="badge bg-secondary">منتهي الصلاحية</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getScheduleNameAttribute()
    {
        $frequency = $this->schedule['frequency'] ?? self::SCHEDULE_NONE;
        switch ($frequency) {
            case self::SCHEDULE_NONE:
                return 'Manual';
            case self::SCHEDULE_DAILY:
                return 'Daily';
            case self::SCHEDULE_WEEKLY:
                return 'Weekly';
            case self::SCHEDULE_MONTHLY:
                return 'Monthly';
            case self::SCHEDULE_QUARTERLY:
                return 'Quarterly';
            case self::SCHEDULE_YEARLY:
                return 'Yearly';
            default:
                return ucfirst($frequency);
        }
    }

    public function getScheduleNameArAttribute()
    {
        $frequency = $this->schedule['frequency'] ?? self::SCHEDULE_NONE;
        switch ($frequency) {
            case self::SCHEDULE_NONE:
                return 'يدوي';
            case self::SCHEDULE_DAILY:
                return 'يومي';
            case self::SCHEDULE_WEEKLY:
                return 'أسبوعي';
            case self::SCHEDULE_MONTHLY:
                return 'شهري';
            case self::SCHEDULE_QUARTERLY:
                return 'ربع سنوي';
            case self::SCHEDULE_YEARLY:
                return 'سنوي';
            default:
                return ucfirst($frequency);
        }
    }

    public function getFileSizeFormattedAttribute()
    {
        if (!$this->file_size) {
            return null;
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getFileSizeFormattedArAttribute()
    {
        return $this->getFileSizeFormattedAttribute();
    }

    public function getFileUrlAttribute()
    {
        if (!$this->file_path) {
            return null;
        }
        if (filter_var($this->file_path, FILTER_VALIDATE_URL)) {
            return $this->file_path;
        }
        return asset('storage/' . $this->file_path);
    }

    public function getGeneratedAtFormattedAttribute()
    {
        return $this->generated_at ? $this->generated_at->format('M d, Y H:i') : null;
    }

    public function getGeneratedAtFormattedArAttribute()
    {
        return $this->generated_at ? $this->generated_at->format('d M Y H:i') : null;
    }

    public function getLastDownloadedAtFormattedAttribute()
    {
        return $this->last_downloaded_at ? $this->last_downloaded_at->format('M d, Y H:i') : null;
    }

    public function getLastDownloadedAtFormattedArAttribute()
    {
        return $this->last_downloaded_at ? $this->last_downloaded_at->format('d M Y H:i') : null;
    }

    public function getLastGeneratedAtFormattedAttribute()
    {
        return $this->last_generated_at ? $this->last_generated_at->format('M d, Y H:i') : null;
    }

    public function getLastGeneratedAtFormattedArAttribute()
    {
        return $this->last_generated_at ? $this->last_generated_at->format('d M Y H:i') : null;
    }

    public function getNextGenerationAtFormattedAttribute()
    {
        return $this->next_generation_at ? $this->next_generation_at->format('M d, Y H:i') : null;
    }

    public function getNextGenerationAtFormattedArAttribute()
    {
        return $this->next_generation_at ? $this->next_generation_at->format('d M Y H:i') : null;
    }

    public function getExpiresAtFormattedAttribute()
    {
        return $this->expires_at ? $this->expires_at->format('M d, Y H:i') : null;
    }

    public function getExpiresAtFormattedArAttribute()
    {
        return $this->expires_at ? $this->expires_at->format('d M Y H:i') : null;
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

    public function getProcessingTimeAttribute()
    {
        if (!$this->created_at || !$this->generated_at) {
            return null;
        }
        return $this->created_at->diffInMinutes($this->generated_at);
    }

    public function getProcessingTimeFormattedAttribute()
    {
        $minutes = $this->getProcessingTimeAttribute();
        if ($minutes === null) {
            return null;
        }
        if ($minutes < 60) {
            return $minutes . ' minutes';
        }
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return $hours . ' hours, ' . $remainingMinutes . ' minutes';
    }

    public function getProcessingTimeFormattedArAttribute()
    {
        $minutes = $this->getProcessingTimeAttribute();
        if ($minutes === null) {
            return null;
        }
        if ($minutes < 60) {
            return $minutes . ' دقيقة';
        }
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return $hours . ' ساعة، ' . $remainingMinutes . ' دقيقة';
    }

    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expires_at) {
            return null;
        }
        return Carbon::now()->diffInDays($this->expires_at, false);
    }

    public function getDaysUntilExpiryFormattedAttribute()
    {
        $days = $this->getDaysUntilExpiryAttribute();
        if ($days === null) {
            return null;
        }
        if ($days > 0) {
            return $days . ' days remaining';
        } elseif ($days < 0) {
            return abs($days) . ' days expired';
        } else {
            return 'Expires today';
        }
    }

    public function getDaysUntilExpiryFormattedArAttribute()
    {
        $days = $this->getDaysUntilExpiryAttribute();
        if ($days === null) {
            return null;
        }
        if ($days > 0) {
            return $days . ' يوم متبقي';
        } elseif ($days < 0) {
            return abs($days) . ' يوم منتهي';
        } else {
            return 'ينتهي اليوم';
        }
    }

    // Business Logic
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing()
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired()
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isPublic()
    {
        return $this->is_public;
    }

    public function isFeatured()
    {
        return $this->is_featured;
    }

    public function hasFile()
    {
        return !empty($this->file_path);
    }

    public function hasDownloads()
    {
        return $this->download_count > 0;
    }

    public function isScheduled()
    {
        $frequency = $this->schedule['frequency'] ?? self::SCHEDULE_NONE;
        return $frequency !== self::SCHEDULE_NONE;
    }

    public function isDueForGeneration()
    {
        return $this->isScheduled() && 
               $this->next_generation_at && 
               $this->next_generation_at <= Carbon::now() && 
               $this->status !== self::STATUS_PROCESSING;
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at < Carbon::now();
    }

    public function canBeDownloaded()
    {
        return $this->isCompleted() && $this->hasFile() && !$this->isExpired();
    }

    public function canBeRegenerated()
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function process()
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
        $this->clearCache();
    }

    public function complete($filePath = null, $fileSize = null)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'generated_at' => Carbon::now(),
            'file_path' => $filePath,
            'file_size' => $fileSize,
        ]);
        $this->updateNextGenerationTime();
        $this->clearCache();
    }

    public function fail($error = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $error,
        ]);
        $this->clearCache();
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason,
        ]);
        $this->clearCache();
    }

    public function expire()
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
        $this->clearCache();
    }

    public function incrementDownloadCount()
    {
        $this->update([
            'download_count' => $this->download_count + 1,
            'last_downloaded_at' => Carbon::now(),
        ]);
        $this->clearCache();
    }

    public function makePublic()
    {
        $this->update(['is_public' => true]);
        $this->clearCache();
    }

    public function makePrivate()
    {
        $this->update(['is_public' => false]);
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

    public function setSchedule($frequency, $time = null, $day = null, $month = null)
    {
        $schedule = [
            'frequency' => $frequency,
            'time' => $time,
            'day' => $day,
            'month' => $month,
        ];
        
        $this->update(['schedule' => $schedule]);
        $this->updateNextGenerationTime();
        $this->clearCache();
    }

    public function removeSchedule()
    {
        $this->update([
            'schedule' => ['frequency' => self::SCHEDULE_NONE],
            'next_generation_at' => null,
        ]);
        $this->clearCache();
    }

    protected function updateNextGenerationTime()
    {
        if (!$this->isScheduled()) {
            return;
        }

        $frequency = $this->schedule['frequency'];
        $time = $this->schedule['time'] ?? '00:00';
        $day = $this->schedule['day'] ?? 1;
        $month = $this->schedule['month'] ?? 1;

        $nextTime = Carbon::now();
        
        switch ($frequency) {
            case self::SCHEDULE_DAILY:
                $nextTime->addDay()->setTimeFromTimeString($time);
                break;
            case self::SCHEDULE_WEEKLY:
                $nextTime->addWeek()->setTimeFromTimeString($time);
                break;
            case self::SCHEDULE_MONTHLY:
                $nextTime->addMonth()->setDay($day)->setTimeFromTimeString($time);
                break;
            case self::SCHEDULE_QUARTERLY:
                $nextTime->addMonths(3)->setDay($day)->setTimeFromTimeString($time);
                break;
            case self::SCHEDULE_YEARLY:
                $nextTime->addYear()->setMonth($month)->setDay($day)->setTimeFromTimeString($time);
                break;
        }

        $this->update(['next_generation_at' => $nextTime]);
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->status = self::STATUS_PENDING;
        $duplicate->generated_at = null;
        $duplicate->file_path = null;
        $duplicate->file_size = null;
        $duplicate->download_count = 0;
        $duplicate->last_downloaded_at = null;
        $duplicate->last_generated_at = null;
        $duplicate->next_generation_at = null;
        $duplicate->save();
        return $duplicate;
    }

    // Static Methods
    public static function getReportsCountByType($type)
    {
        return Cache::remember("reports_count_{$type}", 3600, function () use ($type) {
            return static::where('type', $type)->count();
        });
    }

    public static function getCompletedReportsCount()
    {
        return Cache::remember('completed_reports_count', 3600, function () {
            return static::where('status', self::STATUS_COMPLETED)->count();
        });
    }

    public static function getPendingReportsCount()
    {
        return Cache::remember('pending_reports_count', 3600, function () {
            return static::where('status', self::STATUS_PENDING)->count();
        });
    }

    public static function getScheduledReportsCount()
    {
        return Cache::remember('scheduled_reports_count', 3600, function () {
            return static::scheduled()->count();
        });
    }

    public static function getDueReportsCount()
    {
        return Cache::remember('due_reports_count', 3600, function () {
            return static::dueForGeneration()->count();
        });
    }

    public static function getReportsByType($type)
    {
        return static::where('type', $type)->orderBy('created_at', 'desc')->get();
    }

    public static function getCompletedReports()
    {
        return static::where('status', self::STATUS_COMPLETED)->orderBy('generated_at', 'desc')->get();
    }

    public static function getPendingReports()
    {
        return static::where('status', self::STATUS_PENDING)->orderBy('created_at', 'asc')->get();
    }

    public static function getScheduledReports()
    {
        return static::scheduled()->orderBy('next_generation_at', 'asc')->get();
    }

    public static function getDueReports()
    {
        return static::dueForGeneration()->orderBy('next_generation_at', 'asc')->get();
    }

    public static function getPublicReports()
    {
        return static::public()->orderBy('created_at', 'desc')->get();
    }

    public static function getFeaturedReports()
    {
        return static::featured()->orderBy('created_at', 'desc')->get();
    }

    public static function getPopularReports($limit = 10)
    {
        return static::popular($limit)->get();
    }

    public static function getReportsStats($type = null)
    {
        $query = static::query();
        if ($type) {
            $query->where('type', $type);
        }

        return Cache::remember("reports_stats" . ($type ? "_{$type}" : ""), 3600, function () use ($query) {
            return [
                'total' => $query->count(),
                'pending' => $query->where('status', self::STATUS_PENDING)->count(),
                'processing' => $query->where('status', self::STATUS_PROCESSING)->count(),
                'completed' => $query->where('status', self::STATUS_COMPLETED)->count(),
                'failed' => $query->where('status', self::STATUS_FAILED)->count(),
                'cancelled' => $query->where('status', self::STATUS_CANCELLED)->count(),
                'expired' => $query->where('status', self::STATUS_EXPIRED)->count(),
                'scheduled' => $query->scheduled()->count(),
                'due' => $query->dueForGeneration()->count(),
                'public' => $query->where('is_public', true)->count(),
                'featured' => $query->where('is_featured', true)->count(),
                'total_downloads' => $query->sum('download_count'),
                'total_file_size' => $query->sum('file_size'),
                'average_file_size' => $query->whereNotNull('file_size')->avg('file_size'),
                'success_rate' => $query->count() > 0 ? round(($query->where('status', self::STATUS_COMPLETED)->count() / $query->count()) * 100, 2) : 0,
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        $type = $this->type;
        Cache::forget("reports_count_{$type}");
        Cache::forget("reports_stats_{$type}");
        Cache::forget('completed_reports_count');
        Cache::forget('pending_reports_count');
        Cache::forget('scheduled_reports_count');
        Cache::forget('due_reports_count');
        Cache::forget('reports_stats');
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($report) {
            if (!$report->status) {
                $report->status = self::STATUS_PENDING;
            }
            if (!$report->format) {
                $report->format = self::FORMAT_PDF;
            }
            if (!$report->schedule) {
                $report->schedule = ['frequency' => self::SCHEDULE_NONE];
            }
            if (!isset($report->is_public)) {
                $report->is_public = false;
            }
            if (!isset($report->is_featured)) {
                $report->is_featured = false;
            }
            if (!$report->download_count) {
                $report->download_count = 0;
            }
        });

        static::created(function ($report) {
            $report->clearCache();
        });

        static::updated(function ($report) {
            $report->clearCache();
        });

        static::deleted(function ($report) {
            $report->clearCache();
        });
    }
}