<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'technician_id',
        'status',
        'payment_status',
        'payment_method_id',
        'billing_address_id',
        'shipping_address_id',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'discount_amount',
        'total_amount',
        'currency',
        'exchange_rate',
        'notes',
        'customer_notes',
        'admin_notes',
        'estimated_delivery_date',
        'actual_delivery_date',
        'cancelled_at',
        'cancellation_reason',
        'refunded_at',
        'refund_amount',
        'refund_reason',
        'tracking_number',
        'tracking_url',
        'shipping_method',
        'shipping_provider',
        'invoice_number',
        'invoice_date',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'estimated_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'refund_amount' => 'decimal:2',
        'invoice_date' => 'datetime',
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

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function billingAddress()
    {
        return $this->belongsTo(UserAddress::class, 'billing_address_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(UserAddress::class, 'shipping_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['cancelled', 'refunded']);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus(Builder $query, string $paymentStatus): Builder
    {
        return $query->where('payment_status', $paymentStatus);
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTechnician(Builder $query, int $technicianId): Builder
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByAmountRange(Builder $query, float $minAmount, float $maxAmount): Builder
    {
        return $query->whereBetween('total_amount', [$minAmount, $maxAmount]);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    public function scopeShipped(Builder $query): Builder
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeRefunded(Builder $query): Builder
    {
        return $query->where('status', 'refunded');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePartiallyPaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    // Helper Methods
    public function getStatusBadge(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">في الانتظار</span>',
            'confirmed' => '<span class="badge bg-primary">مؤكد</span>',
            'processing' => '<span class="badge bg-info">قيد المعالجة</span>',
            'shipped' => '<span class="badge bg-info">تم الشحن</span>',
            'delivered' => '<span class="badge bg-success">تم التسليم</span>',
            'cancelled' => '<span class="badge bg-danger">ملغي</span>',
            'refunded' => '<span class="badge bg-secondary">مسترد</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge bg-secondary">' . $this->status . '</span>';
    }

    public function getPaymentStatusBadge(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">في الانتظار</span>',
            'paid' => '<span class="badge bg-success">مدفوع</span>',
            'partial' => '<span class="badge bg-info">مدفوع جزئياً</span>',
            'failed' => '<span class="badge bg-danger">فشل</span>',
            'refunded' => '<span class="badge bg-secondary">مسترد</span>',
        ];
        
        return $badges[$this->payment_status] ?? '<span class="badge bg-secondary">' . $this->payment_status . '</span>';
    }

    public function getSubtotalFormatted(): string
    {
        return number_format($this->subtotal, 2) . ' ريال';
    }

    public function getTaxAmountFormatted(): string
    {
        return number_format($this->tax_amount, 2) . ' ريال';
    }

    public function getShippingCostFormatted(): string
    {
        return number_format($this->shipping_cost, 2) . ' ريال';
    }

    public function getDiscountAmountFormatted(): string
    {
        return number_format($this->discount_amount, 2) . ' ريال';
    }

    public function getTotalAmountFormatted(): string
    {
        return number_format($this->total_amount, 2) . ' ريال';
    }

    public function getRefundAmountFormatted(): string
    {
        return number_format($this->refund_amount, 2) . ' ريال';
    }

    public function getEstimatedDeliveryDateFormatted(): string
    {
        if (!$this->estimated_delivery_date) {
            return 'غير محدد';
        }
        
        return $this->estimated_delivery_date->format('Y-m-d');
    }

    public function getActualDeliveryDateFormatted(): string
    {
        if (!$this->actual_delivery_date) {
            return 'غير محدد';
        }
        
        return $this->actual_delivery_date->format('Y-m-d');
    }

    public function getCancelledAtFormatted(): string
    {
        if (!$this->cancelled_at) {
            return 'غير محدد';
        }
        
        return $this->cancelled_at->format('Y-m-d H:i');
    }

    public function getRefundedAtFormatted(): string
    {
        if (!$this->refunded_at) {
            return 'غير محدد';
        }
        
        return $this->refunded_at->format('Y-m-d H:i');
    }

    public function getInvoiceDateFormatted(): string
    {
        if (!$this->invoice_date) {
            return 'غير محدد';
        }
        
        return $this->invoice_date->format('Y-m-d');
    }

    public function getItemsCount(): int
    {
        return $this->items()->count();
    }

    public function getTotalItemsQuantity(): int
    {
        return $this->items()->sum('quantity');
    }

    public function getPaymentMethodName(): string
    {
        return $this->paymentMethod->getProviderName() ?? 'غير محدد';
    }

    public function getShippingMethodName(): string
    {
        $methods = [
            'standard' => 'شحن عادي',
            'express' => 'شحن سريع',
            'overnight' => 'شحن ليلي',
            'pickup' => 'استلام من المتجر',
            'delivery' => 'توصيل منزلي',
        ];
        
        return $methods[$this->shipping_method] ?? $this->shipping_method;
    }

    public function getShippingProviderName(): string
    {
        $providers = [
            'saudi_post' => 'البريد السعودي',
            'aramex' => 'أرامكس',
            'dhl' => 'DHL',
            'fedex' => 'FedEx',
            'ups' => 'UPS',
            'naqel' => 'ناقل',
        ];
        
        return $providers[$this->shipping_provider] ?? $this->shipping_provider;
    }

    // Business Logic Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isShipped(): bool
    {
        return $this->status === 'shipped';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'partial';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) && !$this->isDelivered();
    }

    public function canBeRefunded(): bool
    {
        return $this->isPaid() && !$this->isRefunded();
    }

    public function canBeShipped(): bool
    {
        return in_array($this->status, ['confirmed', 'processing']) && $this->isPaid();
    }

    public function canBeDelivered(): bool
    {
        return $this->isShipped();
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

    public function process(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function ship(): void
    {
        $this->update(['status' => 'shipped']);
    }

    public function deliver(): void
    {
        $this->update([
            'status' => 'delivered',
            'actual_delivery_date' => now(),
        ]);
    }

    public function refund(float $amount = null, string $reason = null): void
    {
        $refundAmount = $amount ?? $this->total_amount;
        
        $this->update([
            'status' => 'refunded',
            'payment_status' => 'refunded',
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
            'refunded_at' => now(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update(['payment_status' => 'paid']);
    }

    public function markAsPartiallyPaid(): void
    {
        $this->update(['payment_status' => 'partial']);
    }

    public function markAsFailed(): void
    {
        $this->update(['payment_status' => 'failed']);
    }

    public function updateTotals(): void
    {
        $subtotal = $this->items()->sum(\DB::raw('price * quantity'));
        $taxAmount = ($subtotal * 15) / 100; // 15% VAT
        $totalAmount = $subtotal + $taxAmount + $this->shipping_cost - $this->discount_amount;
        
        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    public function getRemainingAmount(): float
    {
        if ($this->isPaid()) {
            return 0;
        }
        
        $paidAmount = $this->payments()->where('status', 'completed')->sum('amount');
        return $this->total_amount - $paidAmount;
    }

    public function getRemainingAmountFormatted(): string
    {
        return number_format($this->getRemainingAmount(), 2) . ' ريال';
    }

    public function getPaidAmount(): float
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getPaidAmountFormatted(): string
    {
        return number_format($this->getPaidAmount(), 2) . ' ريال';
    }

    public function getPaymentPercentage(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }
        
        return round(($this->getPaidAmount() / $this->total_amount) * 100, 2);
    }

    public function isOverdue(): bool
    {
        if (!$this->estimated_delivery_date) {
            return false;
        }
        
        return $this->estimated_delivery_date->isPast() && !$this->isDelivered();
    }

    public function getDaysUntilDelivery(): int
    {
        if (!$this->estimated_delivery_date) {
            return -1;
        }
        
        return now()->diffInDays($this->estimated_delivery_date, false);
    }

    public function getDeliveryStatus(): string
    {
        if ($this->isDelivered()) {
            return 'delivered';
        }
        
        if ($this->isShipped()) {
            return 'shipped';
        }
        
        if ($this->isOverdue()) {
            return 'overdue';
        }
        
        if ($this->estimated_delivery_date && $this->estimated_delivery_date->isFuture()) {
            return 'pending';
        }
        
        return 'unknown';
    }

    public function getDeliveryStatusBadge(): string
    {
        $status = $this->getDeliveryStatus();
        
        $badges = [
            'delivered' => '<span class="badge bg-success">تم التسليم</span>',
            'shipped' => '<span class="badge bg-info">تم الشحن</span>',
            'overdue' => '<span class="badge bg-danger">متأخر</span>',
            'pending' => '<span class="badge bg-warning">في الانتظار</span>',
            'unknown' => '<span class="badge bg-secondary">غير محدد</span>',
        ];
        
        return $badges[$status] ?? $badges['unknown'];
    }

    public function getProfit(): float
    {
        $totalCost = $this->items()->sum(\DB::raw('cost_price * quantity'));
        return $this->total_amount - $totalCost - $this->shipping_cost;
    }

    public function getProfitFormatted(): string
    {
        return number_format($this->getProfit(), 2) . ' ريال';
    }

    public function getProfitMargin(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }
        
        return round(($this->getProfit() / $this->total_amount) * 100, 2);
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($order) {
            // Generate order number if not provided
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . date('Y') . '-' . str_pad(static::whereYear('created_at', date('Y'))->count() + 1, 6, '0', STR_PAD_LEFT);
            }
            
            // Set default values
            if (is_null($order->status)) {
                $order->status = 'pending';
            }
            
            if (is_null($order->payment_status)) {
                $order->payment_status = 'pending';
            }
            
            if (is_null($order->currency)) {
                $order->currency = 'SAR';
            }
            
            if (is_null($order->exchange_rate)) {
                $order->exchange_rate = 1.0000;
            }
            
            if (is_null($order->subtotal)) {
                $order->subtotal = 0;
            }
            
            if (is_null($order->tax_amount)) {
                $order->tax_amount = 0;
            }
            
            if (is_null($order->shipping_cost)) {
                $order->shipping_cost = 0;
            }
            
            if (is_null($order->discount_amount)) {
                $order->discount_amount = 0;
            }
            
            if (is_null($order->total_amount)) {
                $order->total_amount = 0;
            }
        });

        static::created(function ($order) {
            // Clear cache
            Cache::forget("user_orders_count_{$order->user_id}");
            Cache::forget("user_total_spent_{$order->user_id}");
            
            if ($order->technician_id) {
                Cache::forget("technician_orders_count_{$order->technician_id}");
                Cache::forget("technician_total_earnings_{$order->technician_id}");
            }
        });

        static::updated(function ($order) {
            // Clear cache
            Cache::forget("user_orders_count_{$order->user_id}");
            Cache::forget("user_total_spent_{$order->user_id}");
            
            if ($order->technician_id) {
                Cache::forget("technician_orders_count_{$order->technician_id}");
                Cache::forget("technician_total_earnings_{$order->technician_id}");
            }
        });

        static::deleted(function ($order) {
            // Clear cache
            Cache::forget("user_orders_count_{$order->user_id}");
            Cache::forget("user_total_spent_{$order->user_id}");
            
            if ($order->technician_id) {
                Cache::forget("technician_orders_count_{$order->technician_id}");
                Cache::forget("technician_total_earnings_{$order->technician_id}");
            }
        });
    }
} 