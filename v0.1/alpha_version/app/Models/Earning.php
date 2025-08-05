<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Earning extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'order_id',
        'appointment_id',
        'amount',
        'commission_rate',
        'commission_amount',
        'bonus_amount',
        'deduction_amount',
        'net_amount',
        'currency',
        'type',
        'status',
        'payment_method',
        'payment_reference',
        'paid_at',
        'due_date',
        'notes',
        'notes_en',
        'metadata',
    ];

    protected $hidden = [
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'due_date' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Types
    const TYPE_COMMISSION = 'commission';
    const TYPE_BONUS = 'bonus';
    const TYPE_SALARY = 'salary';
    const TYPE_OVERTIME = 'overtime';
    const TYPE_INCENTIVE = 'incentive';
    const TYPE_REFERRAL = 'referral';
    const TYPE_PENALTY = 'penalty';
    const TYPE_DEDUCTION = 'deduction';

    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DISPUTED = 'disputed';

    // Payment Methods
    const PAYMENT_CASH = 'cash';
    const PAYMENT_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_CHECK = 'check';
    const PAYMENT_PAYPAL = 'paypal';
    const PAYMENT_STRIPE = 'stripe';
    const PAYMENT_CRYPTO = 'crypto';

    // Relationships
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
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

    public function scopeByOrder(Builder $query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByAppointment(Builder $query, $appointmentId)
    {
        return $query->where('appointment_id', $appointmentId);
    }

    public function scopeByType(Builder $query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCommission(Builder $query)
    {
        return $query->where('type', self::TYPE_COMMISSION);
    }

    public function scopeBonus(Builder $query)
    {
        return $query->where('type', self::TYPE_BONUS);
    }

    public function scopeSalary(Builder $query)
    {
        return $query->where('type', self::TYPE_SALARY);
    }

    public function scopeOvertime(Builder $query)
    {
        return $query->where('type', self::TYPE_OVERTIME);
    }

    public function scopeIncentive(Builder $query)
    {
        return $query->where('type', self::TYPE_INCENTIVE);
    }

    public function scopeReferral(Builder $query)
    {
        return $query->where('type', self::TYPE_REFERRAL);
    }

    public function scopePenalty(Builder $query)
    {
        return $query->where('type', self::TYPE_PENALTY);
    }

    public function scopeDeduction(Builder $query)
    {
        return $query->where('type', self::TYPE_DEDUCTION);
    }

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePaid(Builder $query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeCancelled(Builder $query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeDisputed(Builder $query)
    {
        return $query->where('status', self::STATUS_DISPUTED);
    }

    public function scopeByPaymentMethod(Builder $query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByAmountRange(Builder $query, $minAmount = null, $maxAmount = null)
    {
        if ($minAmount) {
            $query->where('net_amount', '>=', $minAmount);
        }
        
        if ($maxAmount) {
            $query->where('net_amount', '<=', $maxAmount);
        }
        
        return $query;
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeToday(Builder $query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopeThisWeek(Builder $query)
    {
        return $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    }

    public function scopeThisMonth(Builder $query)
    {
        return $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
    }

    public function scopeThisYear(Builder $query)
    {
        return $query->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);
    }

    public function scopeLastMonth(Builder $query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ]);
    }

    public function scopeLastYear(Builder $query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->subYear()->startOfYear(),
            Carbon::now()->subYear()->endOfYear()
        ]);
    }

    public function scopeOverdue(Builder $query)
    {
        return $query->where('status', '!=', self::STATUS_PAID)
            ->where('due_date', '<', Carbon::today());
    }

    public function scopeDueToday(Builder $query)
    {
        return $query->where('status', '!=', self::STATUS_PAID)
            ->where('due_date', Carbon::today());
    }

    public function scopeDueThisWeek(Builder $query)
    {
        return $query->where('status', '!=', self::STATUS_PAID)
            ->whereBetween('due_date', [Carbon::today(), Carbon::today()->endOfWeek()]);
    }

    public function scopeHighValue(Builder $query, $threshold = 1000)
    {
        return $query->where('net_amount', '>', $threshold);
    }

    public function scopeLowValue(Builder $query, $threshold = 100)
    {
        return $query->where('net_amount', '<', $threshold);
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

    public function getNotesArAttribute()
    {
        return $this->notes;
    }

    public function getNotesEnAttribute()
    {
        return $this->notes_en;
    }

    public function getTypeNameAttribute()
    {
        switch ($this->type) {
            case self::TYPE_COMMISSION:
                return 'Commission';
            case self::TYPE_BONUS:
                return 'Bonus';
            case self::TYPE_SALARY:
                return 'Salary';
            case self::TYPE_OVERTIME:
                return 'Overtime';
            case self::TYPE_INCENTIVE:
                return 'Incentive';
            case self::TYPE_REFERRAL:
                return 'Referral';
            case self::TYPE_PENALTY:
                return 'Penalty';
            case self::TYPE_DEDUCTION:
                return 'Deduction';
            default:
                return ucfirst($this->type);
        }
    }

    public function getTypeNameArAttribute()
    {
        switch ($this->type) {
            case self::TYPE_COMMISSION:
                return 'عمولة';
            case self::TYPE_BONUS:
                return 'مكافأة';
            case self::TYPE_SALARY:
                return 'راتب';
            case self::TYPE_OVERTIME:
                return 'إضافي';
            case self::TYPE_INCENTIVE:
                return 'حافز';
            case self::TYPE_REFERRAL:
                return 'إحالة';
            case self::TYPE_PENALTY:
                return 'عقوبة';
            case self::TYPE_DEDUCTION:
                return 'خصم';
            default:
                return ucfirst($this->type);
        }
    }

    public function getTypeIconAttribute()
    {
        switch ($this->type) {
            case self::TYPE_COMMISSION:
                return 'fas fa-percentage text-primary';
            case self::TYPE_BONUS:
                return 'fas fa-gift text-success';
            case self::TYPE_SALARY:
                return 'fas fa-money-bill-wave text-info';
            case self::TYPE_OVERTIME:
                return 'fas fa-clock text-warning';
            case self::TYPE_INCENTIVE:
                return 'fas fa-star text-warning';
            case self::TYPE_REFERRAL:
                return 'fas fa-user-plus text-info';
            case self::TYPE_PENALTY:
                return 'fas fa-exclamation-triangle text-danger';
            case self::TYPE_DEDUCTION:
                return 'fas fa-minus-circle text-danger';
            default:
                return 'fas fa-money-bill text-secondary';
        }
    }

    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'Pending';
            case self::STATUS_APPROVED:
                return 'Approved';
            case self::STATUS_PAID:
                return 'Paid';
            case self::STATUS_CANCELLED:
                return 'Cancelled';
            case self::STATUS_DISPUTED:
                return 'Disputed';
            default:
                return ucfirst($this->status);
        }
    }

    public function getStatusNameArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'في الانتظار';
            case self::STATUS_APPROVED:
                return 'موافق عليه';
            case self::STATUS_PAID:
                return 'مدفوع';
            case self::STATUS_CANCELLED:
                return 'ملغي';
            case self::STATUS_DISPUTED:
                return 'متنازع عليه';
            default:
                return ucfirst($this->status);
        }
    }

    public function getPaymentMethodNameAttribute()
    {
        switch ($this->payment_method) {
            case self::PAYMENT_CASH:
                return 'Cash';
            case self::PAYMENT_BANK_TRANSFER:
                return 'Bank Transfer';
            case self::PAYMENT_CHECK:
                return 'Check';
            case self::PAYMENT_PAYPAL:
                return 'PayPal';
            case self::PAYMENT_STRIPE:
                return 'Stripe';
            case self::PAYMENT_CRYPTO:
                return 'Cryptocurrency';
            default:
                return ucfirst($this->payment_method);
        }
    }

    public function getPaymentMethodNameArAttribute()
    {
        switch ($this->payment_method) {
            case self::PAYMENT_CASH:
                return 'نقداً';
            case self::PAYMENT_BANK_TRANSFER:
                return 'تحويل بنكي';
            case self::PAYMENT_CHECK:
                return 'شيك';
            case self::PAYMENT_PAYPAL:
                return 'PayPal';
            case self::PAYMENT_STRIPE:
                return 'Stripe';
            case self::PAYMENT_CRYPTO:
                return 'عملة رقمية';
            default:
                return ucfirst($this->payment_method);
        }
    }

    public function getTypeBadgeAttribute()
    {
        switch ($this->type) {
            case self::TYPE_COMMISSION:
                return '<span class="badge bg-primary">Commission</span>';
            case self::TYPE_BONUS:
                return '<span class="badge bg-success">Bonus</span>';
            case self::TYPE_SALARY:
                return '<span class="badge bg-info">Salary</span>';
            case self::TYPE_OVERTIME:
                return '<span class="badge bg-warning">Overtime</span>';
            case self::TYPE_INCENTIVE:
                return '<span class="badge bg-warning">Incentive</span>';
            case self::TYPE_REFERRAL:
                return '<span class="badge bg-info">Referral</span>';
            case self::TYPE_PENALTY:
                return '<span class="badge bg-danger">Penalty</span>';
            case self::TYPE_DEDUCTION:
                return '<span class="badge bg-danger">Deduction</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    public function getTypeBadgeArAttribute()
    {
        switch ($this->type) {
            case self::TYPE_COMMISSION:
                return '<span class="badge bg-primary">عمولة</span>';
            case self::TYPE_BONUS:
                return '<span class="badge bg-success">مكافأة</span>';
            case self::TYPE_SALARY:
                return '<span class="badge bg-info">راتب</span>';
            case self::TYPE_OVERTIME:
                return '<span class="badge bg-warning">إضافي</span>';
            case self::TYPE_INCENTIVE:
                return '<span class="badge bg-warning">حافز</span>';
            case self::TYPE_REFERRAL:
                return '<span class="badge bg-info">إحالة</span>';
            case self::TYPE_PENALTY:
                return '<span class="badge bg-danger">عقوبة</span>';
            case self::TYPE_DEDUCTION:
                return '<span class="badge bg-danger">خصم</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return '<span class="badge bg-warning">Pending</span>';
            case self::STATUS_APPROVED:
                return '<span class="badge bg-info">Approved</span>';
            case self::STATUS_PAID:
                return '<span class="badge bg-success">Paid</span>';
            case self::STATUS_CANCELLED:
                return '<span class="badge bg-secondary">Cancelled</span>';
            case self::STATUS_DISPUTED:
                return '<span class="badge bg-danger">Disputed</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    public function getStatusBadgeArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return '<span class="badge bg-warning">في الانتظار</span>';
            case self::STATUS_APPROVED:
                return '<span class="badge bg-info">موافق عليه</span>';
            case self::STATUS_PAID:
                return '<span class="badge bg-success">مدفوع</span>';
            case self::STATUS_CANCELLED:
                return '<span class="badge bg-secondary">ملغي</span>';
            case self::STATUS_DISPUTED:
                return '<span class="badge bg-danger">متنازع عليه</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getAmountFormattedAttribute()
    {
        return number_format($this->amount, 2) . ' ' . ($this->currency ?: 'SAR');
    }

    public function getAmountFormattedArAttribute()
    {
        return number_format($this->amount, 2) . ' ' . ($this->currency ?: 'ريال');
    }

    public function getCommissionAmountFormattedAttribute()
    {
        return number_format($this->commission_amount, 2) . ' ' . ($this->currency ?: 'SAR');
    }

    public function getCommissionAmountFormattedArAttribute()
    {
        return number_format($this->commission_amount, 2) . ' ' . ($this->currency ?: 'ريال');
    }

    public function getBonusAmountFormattedAttribute()
    {
        return number_format($this->bonus_amount, 2) . ' ' . ($this->currency ?: 'SAR');
    }

    public function getBonusAmountFormattedArAttribute()
    {
        return number_format($this->bonus_amount, 2) . ' ' . ($this->currency ?: 'ريال');
    }

    public function getDeductionAmountFormattedAttribute()
    {
        return number_format($this->deduction_amount, 2) . ' ' . ($this->currency ?: 'SAR');
    }

    public function getDeductionAmountFormattedArAttribute()
    {
        return number_format($this->deduction_amount, 2) . ' ' . ($this->currency ?: 'ريال');
    }

    public function getNetAmountFormattedAttribute()
    {
        return number_format($this->net_amount, 2) . ' ' . ($this->currency ?: 'SAR');
    }

    public function getNetAmountFormattedArAttribute()
    {
        return number_format($this->net_amount, 2) . ' ' . ($this->currency ?: 'ريال');
    }

    public function getCommissionRateFormattedAttribute()
    {
        return $this->commission_rate . '%';
    }

    public function getCommissionRateFormattedArAttribute()
    {
        return $this->commission_rate . '%';
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

    public function getPaidAtFormattedAttribute()
    {
        return $this->paid_at ? $this->paid_at->format('M d, Y H:i') : null;
    }

    public function getPaidAtFormattedArAttribute()
    {
        return $this->paid_at ? $this->paid_at->format('d M Y H:i') : null;
    }

    public function getDueDateFormattedAttribute()
    {
        return $this->due_date ? $this->due_date->format('M d, Y') : null;
    }

    public function getDueDateFormattedArAttribute()
    {
        return $this->due_date ? $this->due_date->format('d M Y') : null;
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

    public function getDaysUntilDueAttribute()
    {
        if (!$this->due_date) {
            return null;
        }
        
        return Carbon::today()->diffInDays($this->due_date, false);
    }

    public function getDaysUntilDueFormattedAttribute()
    {
        $days = $this->getDaysUntilDueAttribute();
        
        if ($days === null) {
            return null;
        }
        
        if ($days > 0) {
            return $days . ' days remaining';
        } elseif ($days < 0) {
            return abs($days) . ' days overdue';
        } else {
            return 'Due today';
        }
    }

    public function getDaysUntilDueFormattedArAttribute()
    {
        $days = $this->getDaysUntilDueAttribute();
        
        if ($days === null) {
            return null;
        }
        
        if ($days > 0) {
            return $days . ' يوم متبقي';
        } elseif ($days < 0) {
            return abs($days) . ' يوم متأخر';
        } else {
            return 'مستحق اليوم';
        }
    }

    public function getPaymentTimeAttribute()
    {
        if (!$this->paid_at) {
            return null;
        }
        
        return $this->created_at->diffForHumans($this->paid_at, true);
    }

    public function getPaymentTimeArAttribute()
    {
        if (!$this->paid_at) {
            return null;
        }
        
        $diff = $this->created_at->diff($this->paid_at);
        
        if ($diff->days > 0) {
            return $diff->days . ' يوم';
        } elseif ($diff->h > 0) {
            return $diff->h . ' ساعة';
        } elseif ($diff->i > 0) {
            return $diff->i . ' دقيقة';
        } else {
            return 'فوراً';
        }
    }

    // Business Logic
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isDisputed()
    {
        return $this->status === self::STATUS_DISPUTED;
    }

    public function isCommission()
    {
        return $this->type === self::TYPE_COMMISSION;
    }

    public function isBonus()
    {
        return $this->type === self::TYPE_BONUS;
    }

    public function isSalary()
    {
        return $this->type === self::TYPE_SALARY;
    }

    public function isOvertime()
    {
        return $this->type === self::TYPE_OVERTIME;
    }

    public function isIncentive()
    {
        return $this->type === self::TYPE_INCENTIVE;
    }

    public function isReferral()
    {
        return $this->type === self::TYPE_REFERRAL;
    }

    public function isPenalty()
    {
        return $this->type === self::TYPE_PENALTY;
    }

    public function isDeduction()
    {
        return $this->type === self::TYPE_DEDUCTION;
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isPaid();
    }

    public function isDueToday()
    {
        return $this->due_date && $this->due_date->isToday() && !$this->isPaid();
    }

    public function isDueThisWeek()
    {
        return $this->due_date && $this->due_date->isCurrentWeek() && !$this->isPaid();
    }

    public function hasOrder()
    {
        return $this->order_id !== null;
    }

    public function hasAppointment()
    {
        return $this->appointment_id !== null;
    }

    public function hasBonus()
    {
        return $this->bonus_amount > 0;
    }

    public function hasDeduction()
    {
        return $this->deduction_amount > 0;
    }

    public function canBeApproved()
    {
        return $this->isPending();
    }

    public function canBePaid()
    {
        return $this->isApproved();
    }

    public function canBeCancelled()
    {
        return $this->isPending() || $this->isApproved();
    }

    public function canBeDisputed()
    {
        return $this->isPaid();
    }

    public function canBeEdited()
    {
        return $this->isPending();
    }

    public function canBeDeleted()
    {
        return $this->isPending() || $this->isCancelled();
    }

    public function approve()
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update(['status' => self::STATUS_APPROVED]);
        $this->clearCache();
        return true;
    }

    public function pay($paymentMethod = null, $paymentReference = null)
    {
        if (!$this->canBePaid()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PAID,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
            'paid_at' => Carbon::now(),
        ]);
        $this->clearCache();
        return true;
    }

    public function cancel($reason = null)
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason ? ($this->notes . "\nCancellation Reason: " . $reason) : $this->notes,
        ]);
        $this->clearCache();
        return true;
    }

    public function dispute($reason = null)
    {
        if (!$this->canBeDisputed()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_DISPUTED,
            'notes' => $reason ? ($this->notes . "\nDispute Reason: " . $reason) : $this->notes,
        ]);
        $this->clearCache();
        return true;
    }

    public function calculateNetAmount()
    {
        $netAmount = $this->commission_amount + $this->bonus_amount - $this->deduction_amount;
        
        $this->update(['net_amount' => $netAmount]);
        $this->clearCache();
        
        return $netAmount;
    }

    public function addBonus($amount, $reason = null)
    {
        $this->update([
            'bonus_amount' => $this->bonus_amount + $amount,
            'notes' => $reason ? ($this->notes . "\nBonus Added: " . $reason) : $this->notes,
        ]);
        
        $this->calculateNetAmount();
        $this->clearCache();
    }

    public function addDeduction($amount, $reason = null)
    {
        $this->update([
            'deduction_amount' => $this->deduction_amount + $amount,
            'notes' => $reason ? ($this->notes . "\nDeduction Added: " . $reason) : $this->notes,
        ]);
        
        $this->calculateNetAmount();
        $this->clearCache();
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->status = self::STATUS_PENDING;
        $duplicate->paid_at = null;
        $duplicate->save();
        
        return $duplicate;
    }

    public function getOrderNumber()
    {
        return $this->order ? $this->order->order_number : null;
    }

    public function getAppointmentNumber()
    {
        return $this->appointment ? $this->appointment->appointment_number : null;
    }

    public function getTechnicianName()
    {
        return $this->technician ? $this->technician->name : null;
    }

    public function getTechnicianNameEn()
    {
        return $this->technician ? $this->technician->name_en : null;
    }

    // Static Methods
    public static function getEarningsCountForTechnician($technicianId)
    {
        return Cache::remember("technician_earnings_count_{$technicianId}", 3600, function () use ($technicianId) {
            return static::where('technician_id', $technicianId)->count();
        });
    }

    public static function getPendingEarningsCount()
    {
        return Cache::remember('pending_earnings_count', 3600, function () {
            return static::where('status', self::STATUS_PENDING)->count();
        });
    }

    public static function getApprovedEarningsCount()
    {
        return Cache::remember('approved_earnings_count', 3600, function () {
            return static::where('status', self::STATUS_APPROVED)->count();
        });
    }

    public static function getPaidEarningsCount()
    {
        return Cache::remember('paid_earnings_count', 3600, function () {
            return static::where('status', self::STATUS_PAID)->count();
        });
    }

    public static function getOverdueEarningsCount()
    {
        return Cache::remember('overdue_earnings_count', 3600, function () {
            return static::where('status', '!=', self::STATUS_PAID)
                ->where('due_date', '<', Carbon::today())
                ->count();
        });
    }

    public static function getEarningsForTechnician($technicianId, $startDate = null, $endDate = null)
    {
        $query = static::where('technician_id', $technicianId);
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    public static function getPendingEarnings()
    {
        return static::where('status', self::STATUS_PENDING)
            ->with(['technician', 'order', 'appointment'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public static function getApprovedEarnings()
    {
        return static::where('status', self::STATUS_APPROVED)
            ->with(['technician', 'order', 'appointment'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public static function getOverdueEarnings()
    {
        return static::where('status', '!=', self::STATUS_PAID)
            ->where('due_date', '<', Carbon::today())
            ->with(['technician', 'order', 'appointment'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public static function getTodayEarnings()
    {
        return static::whereDate('created_at', Carbon::today())
            ->with(['technician', 'order', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getThisWeekEarnings()
    {
        return static::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->with(['technician', 'order', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getThisMonthEarnings()
    {
        return static::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->with(['technician', 'order', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getEarningsByType($type, $limit = 50)
    {
        return static::where('type', $type)
            ->with(['technician', 'order', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getEarningsByStatus($status, $limit = 50)
    {
        return static::where('status', $status)
            ->with(['technician', 'order', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getEarningsStats($technicianId = null)
    {
        $query = static::query();
        
        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }

        return Cache::remember("earnings_stats" . ($technicianId ? "_{$technicianId}" : ""), 3600, function () use ($query) {
            return [
                'total' => $query->count(),
                'pending' => $query->where('status', self::STATUS_PENDING)->count(),
                'approved' => $query->where('status', self::STATUS_APPROVED)->count(),
                'paid' => $query->where('status', self::STATUS_PAID)->count(),
                'cancelled' => $query->where('status', self::STATUS_CANCELLED)->count(),
                'disputed' => $query->where('status', self::STATUS_DISPUTED)->count(),
                'commission' => $query->where('type', self::TYPE_COMMISSION)->count(),
                'bonus' => $query->where('type', self::TYPE_BONUS)->count(),
                'salary' => $query->where('type', self::TYPE_SALARY)->count(),
                'overtime' => $query->where('type', self::TYPE_OVERTIME)->count(),
                'incentive' => $query->where('type', self::TYPE_INCENTIVE)->count(),
                'referral' => $query->where('type', self::TYPE_REFERRAL)->count(),
                'penalty' => $query->where('type', self::TYPE_PENALTY)->count(),
                'deduction' => $query->where('type', self::TYPE_DEDUCTION)->count(),
                'overdue' => $query->where('status', '!=', self::STATUS_PAID)->where('due_date', '<', Carbon::today())->count(),
                'total_amount' => $query->sum('amount'),
                'total_commission' => $query->sum('commission_amount'),
                'total_bonus' => $query->sum('bonus_amount'),
                'total_deduction' => $query->sum('deduction_amount'),
                'total_net' => $query->sum('net_amount'),
                'avg_commission_rate' => $query->where('commission_rate', '>', 0)->avg('commission_rate'),
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        $technicianId = $this->technician_id;
        
        Cache::forget("technician_earnings_count_{$technicianId}");
        Cache::forget('pending_earnings_count');
        Cache::forget('approved_earnings_count');
        Cache::forget('paid_earnings_count');
        Cache::forget('overdue_earnings_count');
        Cache::forget("earnings_stats_{$technicianId}");
        Cache::forget("earnings_stats");
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($earning) {
            if (!$earning->status) {
                $earning->status = self::STATUS_PENDING;
            }
            
            if (!$earning->currency) {
                $earning->currency = 'SAR';
            }
            
            if (!$earning->commission_amount && $earning->amount && $earning->commission_rate) {
                $earning->commission_amount = $earning->amount * ($earning->commission_rate / 100);
            }
            
            if (!$earning->net_amount) {
                $earning->net_amount = $earning->commission_amount + $earning->bonus_amount - $earning->deduction_amount;
            }
        });

        static::created(function ($earning) {
            $earning->clearCache();
        });

        static::updated(function ($earning) {
            $earning->clearCache();
        });

        static::deleted(function ($earning) {
            $earning->clearCache();
        });
    }
}