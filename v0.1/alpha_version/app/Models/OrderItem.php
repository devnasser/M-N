<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'cost_price',
        'total_price',
        'discount_amount',
        'tax_amount',
        'options',
        'notes',
        'status',
        'shipped_at',
        'delivered_at',
        'returned_at',
        'return_reason',
        'refunded_at',
        'refund_amount',
        'refund_reason',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'options' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
        'refunded_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Scopes
    public function scopeByOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeShipped(Builder $query): Builder
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    public function scopeReturned(Builder $query): Builder
    {
        return $query->where('status', 'returned');
    }

    public function scopeRefunded(Builder $query): Builder
    {
        return $query->where('status', 'refunded');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeWithReviews(Builder $query): Builder
    {
        return $query->whereHas('reviews');
    }

    public function scopeWithoutReviews(Builder $query): Builder
    {
        return $query->whereDoesntHave('reviews');
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
            'returned' => '<span class="badge bg-warning">مسترد</span>',
            'refunded' => '<span class="badge bg-secondary">مسترد المال</span>',
            'cancelled' => '<span class="badge bg-danger">ملغي</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge bg-secondary">' . $this->status . '</span>';
    }

    public function getPriceFormatted(): string
    {
        return number_format($this->price, 2) . ' ريال';
    }

    public function getCostPriceFormatted(): string
    {
        return number_format($this->cost_price, 2) . ' ريال';
    }

    public function getTotalPriceFormatted(): string
    {
        return number_format($this->total_price, 2) . ' ريال';
    }

    public function getDiscountAmountFormatted(): string
    {
        return number_format($this->discount_amount, 2) . ' ريال';
    }

    public function getTaxAmountFormatted(): string
    {
        return number_format($this->tax_amount, 2) . ' ريال';
    }

    public function getRefundAmountFormatted(): string
    {
        return number_format($this->refund_amount, 2) . ' ريال';
    }

    public function getProductName(): string
    {
        return $this->product->getLocalizedName() ?? 'منتج غير محدد';
    }

    public function getProductImage(): string
    {
        return $this->product->getMainImage() ?? '/images/placeholder-product.jpg';
    }

    public function getProductUrl(): string
    {
        return $this->product->getUrl() ?? '#';
    }

    public function getProductSku(): string
    {
        return $this->product->sku ?? 'غير محدد';
    }

    public function getQuantityFormatted(): string
    {
        $quantity = $this->quantity;
        
        if ($quantity == 1) {
            return 'قطعة واحدة';
        }
        
        if ($quantity == 2) {
            return 'قطعتان';
        }
        
        if ($quantity >= 3 && $quantity <= 10) {
            return $quantity . ' قطع';
        }
        
        return $quantity . ' قطعة';
    }

    public function getOptions(): array
    {
        return $this->options ?? [];
    }

    public function getOptionValue(string $key, $default = null)
    {
        return data_get($this->options, $key, $default);
    }

    public function getOptionsText(): string
    {
        $options = $this->getOptions();
        
        if (empty($options)) {
            return 'لا توجد خيارات';
        }
        
        $texts = [];
        foreach ($options as $key => $value) {
            $texts[] = $key . ': ' . $value;
        }
        
        return implode(', ', $texts);
    }

    public function getShippedAtFormatted(): string
    {
        if (!$this->shipped_at) {
            return 'غير محدد';
        }
        
        return $this->shipped_at->format('Y-m-d H:i');
    }

    public function getDeliveredAtFormatted(): string
    {
        if (!$this->delivered_at) {
            return 'غير محدد';
        }
        
        return $this->delivered_at->format('Y-m-d H:i');
    }

    public function getReturnedAtFormatted(): string
    {
        if (!$this->returned_at) {
            return 'غير محدد';
        }
        
        return $this->returned_at->format('Y-m-d H:i');
    }

    public function getRefundedAtFormatted(): string
    {
        if (!$this->refunded_at) {
            return 'غير محدد';
        }
        
        return $this->refunded_at->format('Y-m-d H:i');
    }

    public function getSubtotal(): float
    {
        return $this->total_price - $this->tax_amount;
    }

    public function getSubtotalFormatted(): string
    {
        return number_format($this->getSubtotal(), 2) . ' ريال';
    }

    public function getProfit(): float
    {
        return $this->total_price - ($this->cost_price * $this->quantity);
    }

    public function getProfitFormatted(): string
    {
        return number_format($this->getProfit(), 2) . ' ريال';
    }

    public function getProfitMargin(): float
    {
        if ($this->total_price == 0) {
            return 0;
        }
        
        return round(($this->getProfit() / $this->total_price) * 100, 2);
    }

    public function getProfitMarginFormatted(): string
    {
        return $this->getProfitMargin() . '%';
    }

    public function getDiscountPercentage(): float
    {
        $originalPrice = $this->price * $this->quantity;
        
        if ($originalPrice == 0) {
            return 0;
        }
        
        return round(($this->discount_amount / $originalPrice) * 100, 2);
    }

    public function getDiscountPercentageFormatted(): string
    {
        return $this->getDiscountPercentage() . '%';
    }

    public function getRefundPercentage(): float
    {
        if ($this->total_price == 0) {
            return 0;
        }
        
        return round(($this->refund_amount / $this->total_price) * 100, 2);
    }

    public function getRefundPercentageFormatted(): string
    {
        return $this->getRefundPercentage() . '%';
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

    public function isReturned(): bool
    {
        return $this->status === 'returned';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function hasDiscount(): bool
    {
        return $this->discount_amount > 0;
    }

    public function hasRefund(): bool
    {
        return $this->refund_amount > 0;
    }

    public function hasOptions(): bool
    {
        return !empty($this->getOptions());
    }

    public function hasReviews(): bool
    {
        return $this->reviews()->count() > 0;
    }

    public function canBeShipped(): bool
    {
        return in_array($this->status, ['confirmed', 'processing']);
    }

    public function canBeDelivered(): bool
    {
        return $this->isShipped();
    }

    public function canBeReturned(): bool
    {
        return $this->isDelivered() && !$this->isReturned();
    }

    public function canBeRefunded(): bool
    {
        return ($this->isDelivered() || $this->isReturned()) && !$this->isRefunded();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function canBeReviewed(): bool
    {
        return $this->isDelivered() && !$this->hasReviews();
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
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);
    }

    public function deliver(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function return(string $reason = null): void
    {
        $this->update([
            'status' => 'returned',
            'return_reason' => $reason,
            'returned_at' => now(),
        ]);
    }

    public function refund(float $amount = null, string $reason = null): void
    {
        $refundAmount = $amount ?? $this->total_price;
        
        $this->update([
            'status' => 'refunded',
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
            'refunded_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function updateQuantity(int $quantity): void
    {
        $this->update([
            'quantity' => $quantity,
            'total_price' => $this->price * $quantity,
            'tax_amount' => ($this->price * $quantity * 15) / 100,
        ]);
    }

    public function updatePrice(float $price): void
    {
        $this->update([
            'price' => $price,
            'total_price' => $price * $this->quantity,
            'tax_amount' => ($price * $this->quantity * 15) / 100,
        ]);
    }

    public function applyDiscount(float $amount): void
    {
        $this->update([
            'discount_amount' => $amount,
            'total_price' => ($this->price * $this->quantity) - $amount,
        ]);
    }

    public function removeDiscount(): void
    {
        $this->update([
            'discount_amount' => 0,
            'total_price' => $this->price * $this->quantity,
        ]);
    }

    public function getDeliveryTime(): ?int
    {
        if (!$this->shipped_at || !$this->delivered_at) {
            return null;
        }
        
        return $this->shipped_at->diffInHours($this->delivered_at);
    }

    public function getDeliveryTimeFormatted(): string
    {
        $time = $this->getDeliveryTime();
        
        if (is_null($time)) {
            return 'غير متوفر';
        }
        
        if ($time < 24) {
            return $time . ' ساعة';
        }
        
        $days = floor($time / 24);
        $hours = $time % 24;
        
        if ($days == 1) {
            return 'يوم واحد' . ($hours > 0 ? ' و' . $hours . ' ساعة' : '');
        }
        
        return $days . ' أيام' . ($hours > 0 ? ' و' . $hours . ' ساعة' : '');
    }

    public function getReturnTime(): ?int
    {
        if (!$this->delivered_at || !$this->returned_at) {
            return null;
        }
        
        return $this->delivered_at->diffInDays($this->returned_at);
    }

    public function getReturnTimeFormatted(): string
    {
        $time = $this->getReturnTime();
        
        if (is_null($time)) {
            return 'غير متوفر';
        }
        
        if ($time == 0) {
            return 'نفس اليوم';
        }
        
        if ($time == 1) {
            return 'يوم واحد';
        }
        
        if ($time == 2) {
            return 'يومان';
        }
        
        if ($time >= 3 && $time <= 10) {
            return $time . ' أيام';
        }
        
        return $time . ' يوم';
    }

    public function getRefundTime(): ?int
    {
        if (!$this->returned_at || !$this->refunded_at) {
            return null;
        }
        
        return $this->returned_at->diffInDays($this->refunded_at);
    }

    public function getRefundTimeFormatted(): string
    {
        $time = $this->getRefundTime();
        
        if (is_null($time)) {
            return 'غير متوفر';
        }
        
        if ($time == 0) {
            return 'نفس اليوم';
        }
        
        if ($time == 1) {
            return 'يوم واحد';
        }
        
        if ($time == 2) {
            return 'يومان';
        }
        
        if ($time >= 3 && $time <= 10) {
            return $time . ' أيام';
        }
        
        return $time . ' يوم';
    }

    public function getAverageRating(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getReviewsCount(): int
    {
        return $this->reviews()->count();
    }

    public function getReviewsCountFormatted(): string
    {
        $count = $this->getReviewsCount();
        
        if ($count == 0) {
            return 'لا توجد تقييمات';
        }
        
        if ($count == 1) {
            return 'تقييم واحد';
        }
        
        if ($count == 2) {
            return 'تقييمان';
        }
        
        if ($count >= 3 && $count <= 10) {
            return $count . ' تقييمات';
        }
        
        return $count . ' تقييم';
    }

    public function getRatingStars(): string
    {
        $rating = $this->getAverageRating();
        
        if ($rating == 0) {
            return '<span class="text-muted">لا توجد تقييمات</span>';
        }
        
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $stars .= '<i class="fas fa-star text-warning"></i>';
            } else {
                $stars .= '<i class="far fa-star text-muted"></i>';
            }
        }
        
        return $stars . ' <span class="ms-1">(' . number_format($rating, 1) . '/5)</span>';
    }

    // Static Methods
    public static function getTotalItemsCount(int $orderId): int
    {
        return static::byOrder($orderId)->sum('quantity');
    }

    public static function getTotalValue(int $orderId): float
    {
        return static::byOrder($orderId)->sum('total_price');
    }

    public static function getTotalDiscount(int $orderId): float
    {
        return static::byOrder($orderId)->sum('discount_amount');
    }

    public static function getTotalTax(int $orderId): float
    {
        return static::byOrder($orderId)->sum('tax_amount');
    }

    public static function getTotalProfit(int $orderId): float
    {
        return static::byOrder($orderId)->sum(\DB::raw('total_price - (cost_price * quantity)'));
    }

    public static function getTotalRefund(int $orderId): float
    {
        return static::byOrder($orderId)->sum('refund_amount');
    }

    public static function getDeliveredItems(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return static::byOrder($orderId)->delivered()->with('product')->get();
    }

    public static function getReturnedItems(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return static::byOrder($orderId)->returned()->with('product')->get();
    }

    public static function getRefundedItems(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return static::byOrder($orderId)->refunded()->with('product')->get();
    }

    public static function getItemsWithReviews(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return static::byOrder($orderId)->withReviews()->with(['product', 'reviews'])->get();
    }

    public static function getItemsWithoutReviews(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return static::byOrder($orderId)->withoutReviews()->with('product')->get();
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($item) {
            // Set default values
            if (is_null($item->status)) {
                $item->status = 'pending';
            }
            
            if (is_null($item->quantity)) {
                $item->quantity = 1;
            }
            
            if (is_null($item->price)) {
                $item->price = $item->product->getCurrentPrice() ?? 0;
            }
            
            if (is_null($item->cost_price)) {
                $item->cost_price = $item->product->cost_price ?? 0;
            }
            
            if (is_null($item->total_price)) {
                $item->total_price = $item->price * $item->quantity;
            }
            
            if (is_null($item->discount_amount)) {
                $item->discount_amount = 0;
            }
            
            if (is_null($item->tax_amount)) {
                $item->tax_amount = ($item->total_price * 15) / 100; // 15% VAT
            }
            
            if (is_null($item->refund_amount)) {
                $item->refund_amount = 0;
            }
        });

        static::created(function ($item) {
            // Update order totals
            if ($item->order) {
                $item->order->updateTotals();
            }
        });

        static::updated(function ($item) {
            // Update order totals
            if ($item->order) {
                $item->order->updateTotals();
            }
        });

        static::deleted(function ($item) {
            // Update order totals
            if ($item->order) {
                $item->order->updateTotals();
            }
        });
    }
} 