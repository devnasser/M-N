<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'payment_method_id',
        'amount',
        'currency',
        'status',
        'transaction_id',
        'gateway',
        'gateway_response',
        'gateway_error',
        'payment_date',
        'processed_at',
        'failed_at',
        'refunded_at',
        'refund_amount',
        'refund_reason',
        'fee_amount',
        'tax_amount',
        'exchange_rate',
        'notes',
        'notes_en',
        'metadata',
    ];

    protected $hidden = [
        'gateway_response',
        'gateway_error',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'payment_date' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    const STATUS_DISPUTED = 'disputed';
    const STATUS_EXPIRED = 'expired';

    // Gateway constants
    const GATEWAY_STRIPE = 'stripe';
    const GATEWAY_PAYPAL = 'paypal';
    const GATEWAY_MADA = 'mada';
    const GATEWAY_APPLE_PAY = 'apple_pay';
    const GATEWAY_GOOGLE_PAY = 'google_pay';
    const GATEWAY_BANK_TRANSFER = 'bank_transfer';
    const GATEWAY_CASH = 'cash';
    const GATEWAY_CHECK = 'check';

    // Currency constants
    const CURRENCY_SAR = 'SAR';
    const CURRENCY_USD = 'USD';
    const CURRENCY_EUR = 'EUR';

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Scopes
    public function scopeByOrder(Builder $query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByUser(Builder $query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByGateway(Builder $query, $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeByTransactionId(Builder $query, $transactionId)
    {
        return $query->where('transaction_id', $transactionId);
    }

    public function scopeByAmountRange(Builder $query, $minAmount = null, $maxAmount = null)
    {
        if ($minAmount) {
            $query->where('amount', '>=', $minAmount);
        }
        if ($maxAmount) {
            $query->where('amount', '<=', $maxAmount);
        }
        return $query;
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
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

    public function scopeRefunded(Builder $query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    public function scopePartiallyRefunded(Builder $query)
    {
        return $query->where('status', self::STATUS_PARTIALLY_REFUNDED);
    }

    public function scopeDisputed(Builder $query)
    {
        return $query->where('status', self::STATUS_DISPUTED);
    }

    public function scopeExpired(Builder $query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeToday(Builder $query)
    {
        return $query->whereDate('payment_date', Carbon::today());
    }

    public function scopeThisWeek(Builder $query)
    {
        return $query->whereBetween('payment_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    }

    public function scopeThisMonth(Builder $query)
    {
        return $query->whereMonth('payment_date', Carbon::now()->month);
    }

    public function scopeThisYear(Builder $query)
    {
        return $query->whereYear('payment_date', Carbon::now()->year);
    }

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('payment_date', '>=', Carbon::now()->subDays($days));
    }

    public function scopeHighValue(Builder $query, $threshold = 1000)
    {
        return $query->where('amount', '>=', $threshold);
    }

    public function scopeLowValue(Builder $query, $threshold = 100)
    {
        return $query->where('amount', '<=', $threshold);
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
            case self::STATUS_REFUNDED:
                return 'Refunded';
            case self::STATUS_PARTIALLY_REFUNDED:
                return 'Partially Refunded';
            case self::STATUS_DISPUTED:
                return 'Disputed';
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
            case self::STATUS_REFUNDED:
                return 'مسترد';
            case self::STATUS_PARTIALLY_REFUNDED:
                return 'مسترد جزئياً';
            case self::STATUS_DISPUTED:
                return 'متنازع عليه';
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
            case self::STATUS_REFUNDED:
                return '<span class="badge bg-secondary">Refunded</span>';
            case self::STATUS_PARTIALLY_REFUNDED:
                return '<span class="badge bg-warning">Partially Refunded</span>';
            case self::STATUS_DISPUTED:
                return '<span class="badge bg-danger">Disputed</span>';
            case self::STATUS_EXPIRED:
                return '<span class="badge bg-dark">Expired</span>';
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
            case self::STATUS_REFUNDED:
                return '<span class="badge bg-secondary">مسترد</span>';
            case self::STATUS_PARTIALLY_REFUNDED:
                return '<span class="badge bg-warning">مسترد جزئياً</span>';
            case self::STATUS_DISPUTED:
                return '<span class="badge bg-danger">متنازع عليه</span>';
            case self::STATUS_EXPIRED:
                return '<span class="badge bg-dark">منتهي الصلاحية</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getGatewayNameAttribute()
    {
        switch ($this->gateway) {
            case self::GATEWAY_STRIPE:
                return 'Stripe';
            case self::GATEWAY_PAYPAL:
                return 'PayPal';
            case self::GATEWAY_MADA:
                return 'Mada';
            case self::GATEWAY_APPLE_PAY:
                return 'Apple Pay';
            case self::GATEWAY_GOOGLE_PAY:
                return 'Google Pay';
            case self::GATEWAY_BANK_TRANSFER:
                return 'Bank Transfer';
            case self::GATEWAY_CASH:
                return 'Cash';
            case self::GATEWAY_CHECK:
                return 'Check';
            default:
                return ucfirst($this->gateway);
        }
    }

    public function getGatewayNameArAttribute()
    {
        switch ($this->gateway) {
            case self::GATEWAY_STRIPE:
                return 'Stripe';
            case self::GATEWAY_PAYPAL:
                return 'PayPal';
            case self::GATEWAY_MADA:
                return 'مدى';
            case self::GATEWAY_APPLE_PAY:
                return 'Apple Pay';
            case self::GATEWAY_GOOGLE_PAY:
                return 'Google Pay';
            case self::GATEWAY_BANK_TRANSFER:
                return 'تحويل بنكي';
            case self::GATEWAY_CASH:
                return 'نقداً';
            case self::GATEWAY_CHECK:
                return 'شيك';
            default:
                return ucfirst($this->gateway);
        }
    }

    public function getGatewayIconAttribute()
    {
        switch ($this->gateway) {
            case self::GATEWAY_STRIPE:
                return 'fab fa-stripe';
            case self::GATEWAY_PAYPAL:
                return 'fab fa-paypal';
            case self::GATEWAY_MADA:
                return 'fas fa-credit-card';
            case self::GATEWAY_APPLE_PAY:
                return 'fab fa-apple-pay';
            case self::GATEWAY_GOOGLE_PAY:
                return 'fab fa-google-pay';
            case self::GATEWAY_BANK_TRANSFER:
                return 'fas fa-university';
            case self::GATEWAY_CASH:
                return 'fas fa-money-bill-wave';
            case self::GATEWAY_CHECK:
                return 'fas fa-file-invoice';
            default:
                return 'fas fa-credit-card';
        }
    }

    public function getAmountFormattedAttribute()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getAmountFormattedArAttribute()
    {
        $currencySymbol = $this->currency === 'SAR' ? 'ريال' : $this->currency;
        return number_format($this->amount, 2) . ' ' . $currencySymbol;
    }

    public function getRefundAmountFormattedAttribute()
    {
        return $this->refund_amount ? number_format($this->refund_amount, 2) . ' ' . $this->currency : null;
    }

    public function getRefundAmountFormattedArAttribute()
    {
        if (!$this->refund_amount) {
            return null;
        }
        $currencySymbol = $this->currency === 'SAR' ? 'ريال' : $this->currency;
        return number_format($this->refund_amount, 2) . ' ' . $currencySymbol;
    }

    public function getFeeAmountFormattedAttribute()
    {
        return $this->fee_amount ? number_format($this->fee_amount, 2) . ' ' . $this->currency : null;
    }

    public function getFeeAmountFormattedArAttribute()
    {
        if (!$this->fee_amount) {
            return null;
        }
        $currencySymbol = $this->currency === 'SAR' ? 'ريال' : $this->currency;
        return number_format($this->fee_amount, 2) . ' ' . $currencySymbol;
    }

    public function getTaxAmountFormattedAttribute()
    {
        return $this->tax_amount ? number_format($this->tax_amount, 2) . ' ' . $this->currency : null;
    }

    public function getTaxAmountFormattedArAttribute()
    {
        if (!$this->tax_amount) {
            return null;
        }
        $currencySymbol = $this->currency === 'SAR' ? 'ريال' : $this->currency;
        return number_format($this->tax_amount, 2) . ' ' . $currencySymbol;
    }

    public function getNetAmountAttribute()
    {
        return $this->amount - ($this->fee_amount ?: 0);
    }

    public function getNetAmountFormattedAttribute()
    {
        return number_format($this->getNetAmountAttribute(), 2) . ' ' . $this->currency;
    }

    public function getNetAmountFormattedArAttribute()
    {
        $currencySymbol = $this->currency === 'SAR' ? 'ريال' : $this->currency;
        return number_format($this->getNetAmountAttribute(), 2) . ' ' . $currencySymbol;
    }

    public function getPaymentDateFormattedAttribute()
    {
        return $this->payment_date ? $this->payment_date->format('M d, Y H:i') : null;
    }

    public function getPaymentDateFormattedArAttribute()
    {
        return $this->payment_date ? $this->payment_date->format('d M Y H:i') : null;
    }

    public function getProcessedAtFormattedAttribute()
    {
        return $this->processed_at ? $this->processed_at->format('M d, Y H:i') : null;
    }

    public function getProcessedAtFormattedArAttribute()
    {
        return $this->processed_at ? $this->processed_at->format('d M Y H:i') : null;
    }

    public function getFailedAtFormattedAttribute()
    {
        return $this->failed_at ? $this->failed_at->format('M d, Y H:i') : null;
    }

    public function getFailedAtFormattedArAttribute()
    {
        return $this->failed_at ? $this->failed_at->format('d M Y H:i') : null;
    }

    public function getRefundedAtFormattedAttribute()
    {
        return $this->refunded_at ? $this->refunded_at->format('M d, Y H:i') : null;
    }

    public function getRefundedAtFormattedArAttribute()
    {
        return $this->refunded_at ? $this->refunded_at->format('d M Y H:i') : null;
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
        if (!$this->created_at || !$this->processed_at) {
            return null;
        }
        return $this->created_at->diffInMinutes($this->processed_at);
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

    public function getRefundPercentageAttribute()
    {
        if (!$this->refund_amount || $this->amount <= 0) {
            return 0;
        }
        return round(($this->refund_amount / $this->amount) * 100, 2);
    }

    public function getRefundPercentageFormattedAttribute()
    {
        return $this->getRefundPercentageAttribute() . '%';
    }

    public function getRefundPercentageFormattedArAttribute()
    {
        return $this->getRefundPercentageAttribute() . '%';
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

    public function isRefunded()
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isPartiallyRefunded()
    {
        return $this->status === self::STATUS_PARTIALLY_REFUNDED;
    }

    public function isDisputed()
    {
        return $this->status === self::STATUS_DISPUTED;
    }

    public function isExpired()
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function hasRefund()
    {
        return $this->refund_amount > 0;
    }

    public function hasFee()
    {
        return $this->fee_amount > 0;
    }

    public function hasTax()
    {
        return $this->tax_amount > 0;
    }

    public function hasTransactionId()
    {
        return !empty($this->transaction_id);
    }

    public function hasGatewayError()
    {
        return !empty($this->gateway_error);
    }

    public function canBeProcessed()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeRefunded()
    {
        return $this->status === self::STATUS_COMPLETED && !$this->isRefunded();
    }

    public function canBeDisputed()
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_PARTIALLY_REFUNDED]);
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function process()
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
        ]);
        $this->clearCache();
    }

    public function complete($transactionId = null, $gatewayResponse = null)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'transaction_id' => $transactionId,
            'gateway_response' => $gatewayResponse,
            'processed_at' => Carbon::now(),
        ]);
        $this->clearCache();
    }

    public function fail($error = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'gateway_error' => $error,
            'failed_at' => Carbon::now(),
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

    public function refund($amount = null, $reason = null)
    {
        $refundAmount = $amount ?: $this->amount;
        $status = $refundAmount >= $this->amount ? self::STATUS_REFUNDED : self::STATUS_PARTIALLY_REFUNDED;
        
        $this->update([
            'status' => $status,
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
            'refunded_at' => Carbon::now(),
        ]);
        $this->clearCache();
    }

    public function dispute($reason = null)
    {
        $this->update([
            'status' => self::STATUS_DISPUTED,
            'notes' => $reason,
        ]);
        $this->clearCache();
    }

    public function expire()
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
        $this->clearCache();
    }

    public function updateTransactionId($transactionId)
    {
        $this->update(['transaction_id' => $transactionId]);
        $this->clearCache();
    }

    public function updateGatewayResponse($response)
    {
        $this->update(['gateway_response' => $response]);
        $this->clearCache();
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->transaction_id = null;
        $duplicate->status = self::STATUS_PENDING;
        $duplicate->payment_date = null;
        $duplicate->processed_at = null;
        $duplicate->failed_at = null;
        $duplicate->refunded_at = null;
        $duplicate->refund_amount = null;
        $duplicate->gateway_response = null;
        $duplicate->gateway_error = null;
        $duplicate->save();
        return $duplicate;
    }

    // Static Methods
    public static function getPaymentsCountForOrder($orderId)
    {
        return Cache::remember("order_payments_count_{$orderId}", 3600, function () use ($orderId) {
            return static::where('order_id', $orderId)->count();
        });
    }

    public static function getPaymentsCountForUser($userId)
    {
        return Cache::remember("user_payments_count_{$userId}", 3600, function () use ($userId) {
            return static::where('user_id', $userId)->count();
        });
    }

    public static function getCompletedPaymentsCount()
    {
        return Cache::remember('completed_payments_count', 3600, function () {
            return static::where('status', self::STATUS_COMPLETED)->count();
        });
    }

    public static function getFailedPaymentsCount()
    {
        return Cache::remember('failed_payments_count', 3600, function () {
            return static::where('status', self::STATUS_FAILED)->count();
        });
    }

    public static function getRefundedPaymentsCount()
    {
        return Cache::remember('refunded_payments_count', 3600, function () {
            return static::whereIn('status', [self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED])->count();
        });
    }

    public static function getPaymentsForOrder($orderId)
    {
        return static::where('order_id', $orderId)->orderBy('created_at', 'desc')->get();
    }

    public static function getPaymentsForUser($userId)
    {
        return static::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    }

    public static function getCompletedPayments()
    {
        return static::where('status', self::STATUS_COMPLETED)->orderBy('processed_at', 'desc')->get();
    }

    public static function getFailedPayments()
    {
        return static::where('status', self::STATUS_FAILED)->orderBy('failed_at', 'desc')->get();
    }

    public static function getRefundedPayments()
    {
        return static::whereIn('status', [self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED])
            ->orderBy('refunded_at', 'desc')->get();
    }

    public static function getTodayPayments()
    {
        return static::today()->orderBy('payment_date', 'desc')->get();
    }

    public static function getThisWeekPayments()
    {
        return static::thisWeek()->orderBy('payment_date', 'desc')->get();
    }

    public static function getThisMonthPayments()
    {
        return static::thisMonth()->orderBy('payment_date', 'desc')->get();
    }

    public static function getPaymentsByStatus($status)
    {
        return static::where('status', $status)->orderBy('created_at', 'desc')->get();
    }

    public static function getPaymentsByGateway($gateway)
    {
        return static::where('gateway', $gateway)->orderBy('created_at', 'desc')->get();
    }

    public static function getPaymentsStats($orderId = null, $userId = null)
    {
        $query = static::query();
        if ($orderId) {
            $query->where('order_id', $orderId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return Cache::remember("payments_stats" . ($orderId ? "_{$orderId}" : "") . ($userId ? "_{$userId}" : ""), 3600, function () use ($query) {
            return [
                'total' => $query->count(),
                'total_amount' => $query->sum('amount'),
                'total_fees' => $query->sum('fee_amount'),
                'total_tax' => $query->sum('tax_amount'),
                'total_refunds' => $query->sum('refund_amount'),
                'net_amount' => $query->sum('amount') - $query->sum('fee_amount'),
                'pending' => $query->where('status', self::STATUS_PENDING)->count(),
                'processing' => $query->where('status', self::STATUS_PROCESSING)->count(),
                'completed' => $query->where('status', self::STATUS_COMPLETED)->count(),
                'failed' => $query->where('status', self::STATUS_FAILED)->count(),
                'cancelled' => $query->where('status', self::STATUS_CANCELLED)->count(),
                'refunded' => $query->where('status', self::STATUS_REFUNDED)->count(),
                'partially_refunded' => $query->where('status', self::STATUS_PARTIALLY_REFUNDED)->count(),
                'disputed' => $query->where('status', self::STATUS_DISPUTED)->count(),
                'expired' => $query->where('status', self::STATUS_EXPIRED)->count(),
                'success_rate' => $query->count() > 0 ? round(($query->where('status', self::STATUS_COMPLETED)->count() / $query->count()) * 100, 2) : 0,
                'average_amount' => $query->count() > 0 ? round($query->avg('amount'), 2) : 0,
                'average_processing_time' => $query->whereNotNull('processed_at')->avg('processing_time'),
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        $orderId = $this->order_id;
        $userId = $this->user_id;
        Cache::forget("order_payments_count_{$orderId}");
        Cache::forget("user_payments_count_{$userId}");
        Cache::forget("payments_stats_{$orderId}");
        Cache::forget("payments_stats_{$userId}");
        Cache::forget('completed_payments_count');
        Cache::forget('failed_payments_count');
        Cache::forget('refunded_payments_count');
        Cache::forget('payments_stats');
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($payment) {
            if (!$payment->status) {
                $payment->status = self::STATUS_PENDING;
            }
            if (!$payment->currency) {
                $payment->currency = self::CURRENCY_SAR;
            }
            if (!$payment->payment_date) {
                $payment->payment_date = Carbon::now();
            }
        });

        static::created(function ($payment) {
            $payment->clearCache();
        });

        static::updated(function ($payment) {
            $payment->clearCache();
        });

        static::deleted(function ($payment) {
            $payment->clearCache();
        });
    }
}