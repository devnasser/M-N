<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'shop_id',
        'driver_id',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'shipping_address',
        'billing_address',
        'shipping_method',
        'tracking_number',
        'estimated_delivery',
        'delivered_at',
        'notes',
        'cancellation_reason',
        'refund_amount',
        'refund_reason',
        'meta_data',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'meta_data' => 'array',
        'estimated_delivery' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewable_id')
            ->where('reviewable_type', Order::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'processing']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Helper Methods
    public function getStatusBadge(): string
    {
        $badges = [
            'pending' => 'bg-warning',
            'confirmed' => 'bg-info',
            'processing' => 'bg-primary',
            'shipped' => 'bg-info',
            'delivered' => 'bg-success',
            'cancelled' => 'bg-danger',
            'refunded' => 'bg-secondary',
        ];

        $labels = [
            'pending' => 'في الانتظار',
            'confirmed' => 'مؤكد',
            'processing' => 'قيد المعالجة',
            'shipped' => 'تم الشحن',
            'delivered' => 'تم التوصيل',
            'cancelled' => 'ملغي',
            'refunded' => 'مسترد',
        ];

        $color = $badges[$this->status] ?? 'bg-secondary';
        $label = $labels[$this->status] ?? $this->status;

        return "<span class=\"badge {$color}\">{$label}</span>";
    }

    public function getPaymentStatusBadge(): string
    {
        $badges = [
            'pending' => 'bg-warning',
            'paid' => 'bg-success',
            'failed' => 'bg-danger',
            'refunded' => 'bg-secondary',
        ];

        $labels = [
            'pending' => 'في الانتظار',
            'paid' => 'مدفوع',
            'failed' => 'فشل',
            'refunded' => 'مسترد',
        ];

        $color = $badges[$this->payment_status] ?? 'bg-secondary';
        $label = $labels[$this->payment_status] ?? $this->payment_status;

        return "<span class=\"badge {$color}\">{$label}</span>";
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing']);
    }

    public function getEstimatedDeliveryTime(): string
    {
        if ($this->estimated_delivery) {
            return $this->estimated_delivery->format('Y-m-d H:i');
        }
        return 'غير محدد';
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->orderItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $taxAmount = $subtotal * (config('app.vat_rate', 15) / 100);
        $totalAmount = $subtotal + $taxAmount + $this->shipping_amount - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function cancel($reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);
    }

    public function refund($amount = null, $reason = null): void
    {
        $refundAmount = $amount ?? $this->total_amount;
        
        $this->update([
            'status' => 'refunded',
            'payment_status' => 'refunded',
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
        ]);
    }

    public function getShippingAddressText(): string
    {
        if (is_array($this->shipping_address)) {
            return implode(', ', array_filter($this->shipping_address));
        }
        return $this->shipping_address ?? 'غير محدد';
    }

    public function getBillingAddressText(): string
    {
        if (is_array($this->billing_address)) {
            return implode(', ', array_filter($this->billing_address));
        }
        return $this->billing_address ?? 'غير محدد';
    }

    public function getItemsCount(): int
    {
        return $this->orderItems->sum('quantity');
    }

    public function getFormattedTotal(): string
    {
        return number_format($this->total_amount, 2) . ' ' . ($this->currency ?? 'SAR');
    }
} 