<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_id',
        'user_id',
        'amount',
        'currency',
        'status',
        'reason',
        'reason_en',
        'description',
        'description_en',
        'refund_method',
        'transaction_id',
        'gateway',
        'gateway_response',
        'gateway_error',
        'refund_date',
        'processed_at',
        'failed_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
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
        'refund_date' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    // Refund method constants
    const METHOD_ORIGINAL_PAYMENT = 'original_payment';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CHECK = 'check';
    const METHOD_STORE_CREDIT = 'store_credit';
    const METHOD_EXCHANGE = 'exchange';

    // Reason constants
    const REASON_DEFECTIVE_PRODUCT = 'defective_product';
    const REASON_WRONG_ITEM = 'wrong_item';
    const REASON_DAMAGED = 'damaged';
    const REASON_NOT_AS_DESCRIBED = 'not_as_described';
    const REASON_SIZE_ISSUE = 'size_issue';
    const REASON_COLOR_ISSUE = 'color_issue';
    const REASON_DELIVERY_ISSUE = 'delivery_issue';
    const REASON_CUSTOMER_CHANGE_MIND = 'customer_change_mind';
    const REASON_DUPLICATE_ORDER = 'duplicate_order';
    const REASON_PRICE_ERROR = 'price_error';
    const REASON_OTHER = 'other';

    // Gateway constants
    const GATEWAY_STRIPE = 'stripe';
    const GATEWAY_PAYPAL = 'paypal';
    const GATEWAY_MADA = 'mada';
    const GATEWAY_BANK_TRANSFER = 'bank_transfer';
    const GATEWAY_MANUAL = 'manual';

    // Currency constants
    const CURRENCY_SAR = 'SAR';
    const CURRENCY_USD = 'USD';
    const CURRENCY_EUR = 'EUR';

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
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

    public function scopeByPayment(Builder $query, $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    public function scopeByUser(Builder $query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByReason(Builder $query, $reason)
    {
        return $query->where('reason', $reason);
    }

    public function scopeByRefundMethod(Builder $query, $method)
    {
        return $query->where('refund_method', $method);
    }

    public function scopeByGateway(Builder $query, $gateway)
    {
        return $query->where('gateway', $gateway);
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
        return $query->whereBetween('refund_date', [$startDate, $endDate]);
    }

    public function scopePending(Builder $query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query)
    {
        return $query->where('status', self::STATUS_APPROVED);
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

    public function scopeRejected(Builder $query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeCancelled(Builder $query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeToday(Builder $query)
    {
        return $query->whereDate('refund_date', Carbon::today());
    }

    public function scopeThisWeek(Builder $query)
    {
        return $query->whereBetween('refund_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    }

    public function scopeThisMonth(Builder $query)
    {
        return $query->whereMonth('refund_date', Carbon::now()->month);
    }

    public function scopeThisYear(Builder $query)
    {
        return $query->whereYear('refund_date', Carbon::now()->year);
    }

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('refund_date', '>=', Carbon::now()->subDays($days));
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
    public function getReasonAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->reason) {
            return $this->reason;
        }
        return $value ?: $this->reason_en;
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

    public function getReasonArAttribute()
    {
        return $this->reason;
    }

    public function getDescriptionArAttribute()
    {
        return $this->description;
    }

    public function getNotesArAttribute()
    {
        return $this->notes;
    }

    public function getReasonEnAttribute()
    {
        return $this->reason_en;
    }

    public function getDescriptionEnAttribute()
    {
        return $this->description_en;
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
            case self::STATUS_APPROVED:
                return 'Approved';
            case self::STATUS_PROCESSING:
                return 'Processing';
            case self::STATUS_COMPLETED:
                return 'Completed';
            case self::STATUS_FAILED:
                return 'Failed';
            case self::STATUS_REJECTED:
                return 'Rejected';
            case self::STATUS_CANCELLED:
                return 'Cancelled';
            default:
                return 'Unknown';
        }
    }

    public function getStatusNameArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'في الانتظار';
            case self::STATUS_APPROVED:
                return 'موافق عليه';
            case self::STATUS_PROCESSING:
                return 'قيد المعالجة';
            case self::STATUS_COMPLETED:
                return 'مكتمل';
            case self::STATUS_FAILED:
                return 'فشل';
            case self::STATUS_REJECTED:
                return 'مرفوض';
            case self::STATUS_CANCELLED:
                return 'ملغي';
            default:
                return 'غير معروف';
        }
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return '<span class="badge bg-warning">Pending</span>';
            case self::STATUS_APPROVED:
                return '<span class="badge bg-info">Approved</span>';
            case self::STATUS_PROCESSING:
                return '<span class="badge bg-primary">Processing</span>';
            case self::STATUS_COMPLETED:
                return '<span class="badge bg-success">Completed</span>';
            case self::STATUS_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            case self::STATUS_REJECTED:
                return '<span class="badge bg-danger">Rejected</span>';
            case self::STATUS_CANCELLED:
                return '<span class="badge bg-dark">Cancelled</span>';
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
            case self::STATUS_PROCESSING:
                return '<span class="badge bg-primary">قيد المعالجة</span>';
            case self::STATUS_COMPLETED:
                return '<span class="badge bg-success">مكتمل</span>';
            case self::STATUS_FAILED:
                return '<span class="badge bg-danger">فشل</span>';
            case self::STATUS_REJECTED:
                return '<span class="badge bg-danger">مرفوض</span>';
            case self::STATUS_CANCELLED:
                return '<span class="badge bg-dark">ملغي</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getReasonNameAttribute()
    {
        switch ($this->reason) {
            case self::REASON_DEFECTIVE_PRODUCT:
                return 'Defective Product';
            case self::REASON_WRONG_ITEM:
                return 'Wrong Item';
            case self::REASON_DAMAGED:
                return 'Damaged';
            case self::REASON_NOT_AS_DESCRIBED:
                return 'Not as Described';
            case self::REASON_SIZE_ISSUE:
                return 'Size Issue';
            case self::REASON_COLOR_ISSUE:
                return 'Color Issue';
            case self::REASON_DELIVERY_ISSUE:
                return 'Delivery Issue';
            case self::REASON_CUSTOMER_CHANGE_MIND:
                return 'Customer Changed Mind';
            case self::REASON_DUPLICATE_ORDER:
                return 'Duplicate Order';
            case self::REASON_PRICE_ERROR:
                return 'Price Error';
            case self::REASON_OTHER:
                return 'Other';
            default:
                return ucfirst(str_replace('_', ' ', $this->reason));
        }
    }

    public function getReasonNameArAttribute()
    {
        switch ($this->reason) {
            case self::REASON_DEFECTIVE_PRODUCT:
                return 'منتج معيب';
            case self::REASON_WRONG_ITEM:
                return 'عنصر خاطئ';
            case self::REASON_DAMAGED:
                return 'تالف';
            case self::REASON_NOT_AS_DESCRIBED:
                return 'ليس كما هو موصوف';
            case self::REASON_SIZE_ISSUE:
                return 'مشكلة في الحجم';
            case self::REASON_COLOR_ISSUE:
                return 'مشكلة في اللون';
            case self::REASON_DELIVERY_ISSUE:
                return 'مشكلة في التوصيل';
            case self::REASON_CUSTOMER_CHANGE_MIND:
                return 'العميل غير رأيه';
            case self::REASON_DUPLICATE_ORDER:
                return 'طلب مكرر';
            case self::REASON_PRICE_ERROR:
                return 'خطأ في السعر';
            case self::REASON_OTHER:
                return 'أخرى';
            default:
                return ucfirst(str_replace('_', ' ', $this->reason));
        }
    }

    public function getRefundMethodNameAttribute()
    {
        switch ($this->refund_method) {
            case self::METHOD_ORIGINAL_PAYMENT:
                return 'Original Payment Method';
            case self::METHOD_BANK_TRANSFER:
                return 'Bank Transfer';
            case self::METHOD_CHECK:
                return 'Check';
            case self::METHOD_STORE_CREDIT:
                return 'Store Credit';
            case self::METHOD_EXCHANGE:
                return 'Exchange';
            default:
                return ucfirst(str_replace('_', ' ', $this->refund_method));
        }
    }

    public function getRefundMethodNameArAttribute()
    {
        switch ($this->refund_method) {
            case self::METHOD_ORIGINAL_PAYMENT:
                return 'طريقة الدفع الأصلية';
            case self::METHOD_BANK_TRANSFER:
                return 'تحويل بنكي';
            case self::METHOD_CHECK:
                return 'شيك';
            case self::METHOD_STORE_CREDIT:
                return 'رصيد المتجر';
            case self::METHOD_EXCHANGE:
                return 'استبدال';
            default:
                return ucfirst(str_replace('_', ' ', $this->refund_method));
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
            case self::GATEWAY_BANK_TRANSFER:
                return 'Bank Transfer';
            case self::GATEWAY_MANUAL:
                return 'Manual';
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
            case self::GATEWAY_BANK_TRANSFER:
                return 'تحويل بنكي';
            case self::GATEWAY_MANUAL:
                return 'يدوي';
            default:
                return ucfirst($this->gateway);
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

    public function getRefundDateFormattedAttribute()
    {
        return $this->refund_date ? $this->refund_date->format('M d, Y H:i') : null;
    }

    public function getRefundDateFormattedArAttribute()
    {
        return $this->refund_date ? $this->refund_date->format('d M Y H:i') : null;
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

    public function getApprovedAtFormattedAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('M d, Y H:i') : null;
    }

    public function getApprovedAtFormattedArAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('d M Y H:i') : null;
    }

    public function getRejectedAtFormattedAttribute()
    {
        return $this->rejected_at ? $this->rejected_at->format('M d, Y H:i') : null;
    }

    public function getRejectedAtFormattedArAttribute()
    {
        return $this->rejected_at ? $this->rejected_at->format('d M Y H:i') : null;
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
        if (!$this->refund_date || !$this->processed_at) {
            return null;
        }
        return $this->refund_date->diffInMinutes($this->processed_at);
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

    public function getApprovalTimeAttribute()
    {
        if (!$this->created_at || !$this->approved_at) {
            return null;
        }
        return $this->created_at->diffInMinutes($this->approved_at);
    }

    public function getApprovalTimeFormattedAttribute()
    {
        $minutes = $this->getApprovalTimeAttribute();
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

    public function getApprovalTimeFormattedArAttribute()
    {
        $minutes = $this->getApprovalTimeAttribute();
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

    // Business Logic
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
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

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function hasTransactionId()
    {
        return !empty($this->transaction_id);
    }

    public function hasGatewayError()
    {
        return !empty($this->gateway_error);
    }

    public function hasRejectionReason()
    {
        return !empty($this->rejection_reason);
    }

    public function canBeApproved()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeRejected()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeProcessed()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    public function approve($approvedBy = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => Carbon::now(),
        ]);
        $this->clearCache();
    }

    public function reject($rejectedBy = null, $reason = null)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_by' => $rejectedBy,
            'rejected_at' => Carbon::now(),
            'rejection_reason' => $reason,
        ]);
        $this->clearCache();
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
        $duplicate->refund_date = null;
        $duplicate->processed_at = null;
        $duplicate->failed_at = null;
        $duplicate->approved_at = null;
        $duplicate->rejected_at = null;
        $duplicate->gateway_response = null;
        $duplicate->gateway_error = null;
        $duplicate->save();
        return $duplicate;
    }

    // Static Methods
    public static function getRefundsCountForOrder($orderId)
    {
        return Cache::remember("order_refunds_count_{$orderId}", 3600, function () use ($orderId) {
            return static::where('order_id', $orderId)->count();
        });
    }

    public static function getRefundsCountForPayment($paymentId)
    {
        return Cache::remember("payment_refunds_count_{$paymentId}", 3600, function () use ($paymentId) {
            return static::where('payment_id', $paymentId)->count();
        });
    }

    public static function getRefundsCountForUser($userId)
    {
        return Cache::remember("user_refunds_count_{$userId}", 3600, function () use ($userId) {
            return static::where('user_id', $userId)->count();
        });
    }

    public static function getPendingRefundsCount()
    {
        return Cache::remember('pending_refunds_count', 3600, function () {
            return static::where('status', self::STATUS_PENDING)->count();
        });
    }

    public static function getCompletedRefundsCount()
    {
        return Cache::remember('completed_refunds_count', 3600, function () {
            return static::where('status', self::STATUS_COMPLETED)->count();
        });
    }

    public static function getRejectedRefundsCount()
    {
        return Cache::remember('rejected_refunds_count', 3600, function () {
            return static::where('status', self::STATUS_REJECTED)->count();
        });
    }

    public static function getRefundsForOrder($orderId)
    {
        return static::where('order_id', $orderId)->orderBy('created_at', 'desc')->get();
    }

    public static function getRefundsForPayment($paymentId)
    {
        return static::where('payment_id', $paymentId)->orderBy('created_at', 'desc')->get();
    }

    public static function getRefundsForUser($userId)
    {
        return static::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    }

    public static function getPendingRefunds()
    {
        return static::where('status', self::STATUS_PENDING)->orderBy('created_at', 'asc')->get();
    }

    public static function getCompletedRefunds()
    {
        return static::where('status', self::STATUS_COMPLETED)->orderBy('processed_at', 'desc')->get();
    }

    public static function getRejectedRefunds()
    {
        return static::where('status', self::STATUS_REJECTED)->orderBy('rejected_at', 'desc')->get();
    }

    public static function getTodayRefunds()
    {
        return static::today()->orderBy('refund_date', 'desc')->get();
    }

    public static function getThisWeekRefunds()
    {
        return static::thisWeek()->orderBy('refund_date', 'desc')->get();
    }

    public static function getThisMonthRefunds()
    {
        return static::thisMonth()->orderBy('refund_date', 'desc')->get();
    }

    public static function getRefundsByStatus($status)
    {
        return static::where('status', $status)->orderBy('created_at', 'desc')->get();
    }

    public static function getRefundsByReason($reason)
    {
        return static::where('reason', $reason)->orderBy('created_at', 'desc')->get();
    }

    public static function getRefundsByGateway($gateway)
    {
        return static::where('gateway', $gateway)->orderBy('created_at', 'desc')->get();
    }

    public static function getRefundsStats($orderId = null, $paymentId = null, $userId = null)
    {
        $query = static::query();
        if ($orderId) {
            $query->where('order_id', $orderId);
        }
        if ($paymentId) {
            $query->where('payment_id', $paymentId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return Cache::remember("refunds_stats" . ($orderId ? "_{$orderId}" : "") . ($paymentId ? "_{$paymentId}" : "") . ($userId ? "_{$userId}" : ""), 3600, function () use ($query) {
            return [
                'total' => $query->count(),
                'total_amount' => $query->sum('amount'),
                'pending' => $query->where('status', self::STATUS_PENDING)->count(),
                'approved' => $query->where('status', self::STATUS_APPROVED)->count(),
                'processing' => $query->where('status', self::STATUS_PROCESSING)->count(),
                'completed' => $query->where('status', self::STATUS_COMPLETED)->count(),
                'failed' => $query->where('status', self::STATUS_FAILED)->count(),
                'rejected' => $query->where('status', self::STATUS_REJECTED)->count(),
                'cancelled' => $query->where('status', self::STATUS_CANCELLED)->count(),
                'approval_rate' => $query->count() > 0 ? round(($query->where('status', self::STATUS_APPROVED)->count() / $query->count()) * 100, 2) : 0,
                'completion_rate' => $query->count() > 0 ? round(($query->where('status', self::STATUS_COMPLETED)->count() / $query->count()) * 100, 2) : 0,
                'average_amount' => $query->count() > 0 ? round($query->avg('amount'), 2) : 0,
                'average_processing_time' => $query->whereNotNull('processed_at')->avg('processing_time'),
                'average_approval_time' => $query->whereNotNull('approved_at')->avg('approval_time'),
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        $orderId = $this->order_id;
        $paymentId = $this->payment_id;
        $userId = $this->user_id;
        Cache::forget("order_refunds_count_{$orderId}");
        Cache::forget("payment_refunds_count_{$paymentId}");
        Cache::forget("user_refunds_count_{$userId}");
        Cache::forget("refunds_stats_{$orderId}");
        Cache::forget("refunds_stats_{$paymentId}");
        Cache::forget("refunds_stats_{$userId}");
        Cache::forget('pending_refunds_count');
        Cache::forget('completed_refunds_count');
        Cache::forget('rejected_refunds_count');
        Cache::forget('refunds_stats');
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($refund) {
            if (!$refund->status) {
                $refund->status = self::STATUS_PENDING;
            }
            if (!$refund->currency) {
                $refund->currency = self::CURRENCY_SAR;
            }
            if (!$refund->refund_date) {
                $refund->refund_date = Carbon::now();
            }
            if (!$refund->refund_method) {
                $refund->refund_method = self::METHOD_ORIGINAL_PAYMENT;
            }
        });

        static::created(function ($refund) {
            $refund->clearCache();
        });

        static::updated(function ($refund) {
            $refund->clearCache();
        });

        static::deleted(function ($refund) {
            $refund->clearCache();
        });
    }
}