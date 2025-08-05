<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'discount_amount',
        'total_amount',
        'currency',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'cart_coupon');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeAbandoned(Builder $query, int $hours = 24): Builder
    {
        return $query->where('status', 'active')
                    ->where('updated_at', '<=', now()->subHours($hours));
    }

    // Helper Methods
    public function getStatusBadge(): string
    {
        $badges = [
            'active' => '<span class="badge bg-success">نشط</span>',
            'converted' => '<span class="badge bg-primary">محول</span>',
            'abandoned' => '<span class="badge bg-warning">مهجور</span>',
            'expired' => '<span class="badge bg-danger">منتهي الصلاحية</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge bg-secondary">' . $this->status . '</span>';
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

    public function getItemsCount(): int
    {
        return $this->items()->sum('quantity');
    }

    public function getItemsCountFormatted(): string
    {
        $count = $this->getItemsCount();
        
        if ($count == 0) {
            return 'لا توجد منتجات';
        }
        
        if ($count == 1) {
            return 'منتج واحد';
        }
        
        if ($count == 2) {
            return 'منتجان';
        }
        
        if ($count >= 3 && $count <= 10) {
            return $count . ' منتجات';
        }
        
        return $count . ' منتج';
    }

    public function getUniqueItemsCount(): int
    {
        return $this->items()->count();
    }

    public function getUniqueItemsCountFormatted(): string
    {
        $count = $this->getUniqueItemsCount();
        
        if ($count == 0) {
            return 'لا توجد منتجات';
        }
        
        if ($count == 1) {
            return 'منتج واحد';
        }
        
        if ($count == 2) {
            return 'منتجان';
        }
        
        if ($count >= 3 && $count <= 10) {
            return $count . ' منتجات';
        }
        
        return $count . ' منتج';
    }

    public function getWeight(): float
    {
        return $this->items()->sum(\DB::raw('quantity * weight'));
    }

    public function getWeightFormatted(): string
    {
        $weight = $this->getWeight();
        
        if ($weight == 0) {
            return 'غير محدد';
        }
        
        return number_format($weight, 3) . ' كجم';
    }

    public function getVolume(): float
    {
        return $this->items()->sum(\DB::raw('quantity * (length * width * height)'));
    }

    public function getVolumeFormatted(): string
    {
        $volume = $this->getVolume();
        
        if ($volume == 0) {
            return 'غير محدد';
        }
        
        return number_format($volume, 2) . ' سم³';
    }

    public function getEstimatedDeliveryTime(): string
    {
        $items = $this->items()->with('product')->get();
        
        if ($items->isEmpty()) {
            return 'غير محدد';
        }
        
        $maxDays = 0;
        foreach ($items as $item) {
            if ($item->product && $item->product->shipping_info) {
                $shippingInfo = $item->product->shipping_info;
                $deliveryDays = $shippingInfo['delivery_days'] ?? 3;
                $maxDays = max($maxDays, $deliveryDays);
            }
        }
        
        if ($maxDays == 0) {
            return '3-5 أيام عمل';
        }
        
        if ($maxDays == 1) {
            return 'يوم واحد';
        }
        
        if ($maxDays == 2) {
            return 'يومان';
        }
        
        if ($maxDays >= 3 && $maxDays <= 10) {
            return $maxDays . ' أيام';
        }
        
        return $maxDays . ' يوم';
    }

    public function getAbandonedTime(): ?string
    {
        if ($this->status !== 'active') {
            return null;
        }
        
        $hoursSinceUpdate = $this->updated_at->diffInHours(now());
        
        if ($hoursSinceUpdate < 1) {
            return 'أقل من ساعة';
        }
        
        if ($hoursSinceUpdate < 24) {
            return $hoursSinceUpdate . ' ساعة';
        }
        
        $days = floor($hoursSinceUpdate / 24);
        $remainingHours = $hoursSinceUpdate % 24;
        
        if ($days == 1) {
            return 'يوم واحد' . ($remainingHours > 0 ? ' و' . $remainingHours . ' ساعة' : '');
        }
        
        return $days . ' أيام' . ($remainingHours > 0 ? ' و' . $remainingHours . ' ساعة' : '');
    }

    // Business Logic Methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    public function hasItems(): bool
    {
        return $this->items()->count() > 0;
    }

    public function hasCoupons(): bool
    {
        return $this->coupons()->count() > 0;
    }

    public function canBeConverted(): bool
    {
        return $this->isActive() && $this->hasItems();
    }

    public function convert(): void
    {
        $this->update(['status' => 'converted']);
    }

    public function abandon(): void
    {
        $this->update(['status' => 'abandoned']);
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function reactivate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function addItem(int $productId, int $quantity = 1, array $options = []): CartItem
    {
        $existingItem = $this->items()->where('product_id', $productId)->first();
        
        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);
            $existingItem->update(['options' => array_merge($existingItem->options ?? [], $options)]);
            return $existingItem;
        }
        
        $product = Product::find($productId);
        
        if (!$product) {
            throw new \Exception('المنتج غير موجود');
        }
        
        return $this->items()->create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $product->getCurrentPrice(),
            'options' => $options,
        ]);
    }

    public function updateItem(int $itemId, int $quantity, array $options = []): bool
    {
        $item = $this->items()->find($itemId);
        
        if (!$item) {
            return false;
        }
        
        if ($quantity <= 0) {
            $item->delete();
            return true;
        }
        
        $item->update([
            'quantity' => $quantity,
            'options' => $options,
        ]);
        
        return true;
    }

    public function removeItem(int $itemId): bool
    {
        $item = $this->items()->find($itemId);
        
        if (!$item) {
            return false;
        }
        
        $item->delete();
        return true;
    }

    public function clear(): void
    {
        $this->items()->delete();
        $this->coupons()->detach();
        $this->updateTotals();
    }

    public function updateTotals(): void
    {
        $subtotal = $this->items()->sum(\DB::raw('price * quantity'));
        $taxAmount = ($subtotal * 15) / 100; // 15% VAT
        $shippingCost = $this->calculateShippingCost();
        $discountAmount = $this->calculateDiscountAmount();
        $totalAmount = $subtotal + $taxAmount + $shippingCost - $discountAmount;
        
        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_cost' => $shippingCost,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    public function calculateShippingCost(): float
    {
        $items = $this->items()->with('product')->get();
        $totalWeight = 0;
        $baseCost = 15.0; // تكلفة أساسية
        
        foreach ($items as $item) {
            if ($item->product) {
                $weight = $item->product->weight ?? 0;
                $totalWeight += $weight * $item->quantity;
            }
        }
        
        $weightCost = $totalWeight * 2; // 2 ريال لكل كيلو
        return $baseCost + $weightCost;
    }

    public function calculateDiscountAmount(): float
    {
        $discountAmount = 0;
        $coupons = $this->coupons()->where('is_active', true)->get();
        
        foreach ($coupons as $coupon) {
            if ($coupon->isValid()) {
                if ($coupon->type === 'percentage') {
                    $discountAmount += ($this->subtotal * $coupon->value) / 100;
                } else {
                    $discountAmount += $coupon->value;
                }
            }
        }
        
        return min($discountAmount, $this->subtotal);
    }

    public function applyCoupon(string $code): bool
    {
        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$coupon) {
            return false;
        }
        
        if (!$coupon->isValid()) {
            return false;
        }
        
        // Check if coupon is already applied
        if ($this->coupons()->where('coupon_id', $coupon->id)->exists()) {
            return false;
        }
        
        $this->coupons()->attach($coupon->id);
        $this->updateTotals();
        
        return true;
    }

    public function removeCoupon(int $couponId): bool
    {
        $this->coupons()->detach($couponId);
        $this->updateTotals();
        
        return true;
    }

    public function getAppliedCoupons(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->coupons()->where('is_active', true)->get();
    }

    public function getTotalDiscountAmount(): float
    {
        return $this->discount_amount;
    }

    public function getTotalDiscountAmountFormatted(): string
    {
        return number_format($this->getTotalDiscountAmount(), 2) . ' ريال';
    }

    public function getSavingsPercentage(): float
    {
        if ($this->subtotal == 0) {
            return 0;
        }
        
        return round(($this->discount_amount / $this->subtotal) * 100, 2);
    }

    public function getSavingsPercentageFormatted(): string
    {
        return $this->getSavingsPercentage() . '%';
    }

    public function validateStock(): array
    {
        $errors = [];
        $items = $this->items()->with('product')->get();
        
        foreach ($items as $item) {
            if (!$item->product) {
                $errors[] = 'المنتج غير موجود';
                continue;
            }
            
            if (!$item->product->isActive()) {
                $errors[] = 'المنتج ' . $item->product->getLocalizedName() . ' غير متاح';
                continue;
            }
            
            if (!$item->product->isInStock()) {
                $errors[] = 'المنتج ' . $item->product->getLocalizedName() . ' نفذ من المخزون';
                continue;
            }
            
            if ($item->product->available_quantity < $item->quantity) {
                $errors[] = 'الكمية المطلوبة للمنتج ' . $item->product->getLocalizedName() . ' غير متوفرة. المتوفر: ' . $item->product->available_quantity;
            }
        }
        
        return $errors;
    }

    public function canBeOrdered(): bool
    {
        return empty($this->validateStock());
    }

    public function createOrder(): Order
    {
        if (!$this->canBeOrdered()) {
            throw new \Exception('لا يمكن إنشاء الطلب. يرجى مراجعة المنتجات في السلة.');
        }
        
        $order = Order::create([
            'user_id' => $this->user_id,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'shipping_cost' => $this->shipping_cost,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency ?? 'SAR',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
        
        // Copy items to order
        foreach ($this->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'options' => $item->options,
            ]);
            
            // Update product stats
            $item->product->incrementOrderCount();
            $item->product->deductStock($item->quantity);
        }
        
        // Convert cart
        $this->convert();
        
        return $order;
    }

    public function mergeWith(Cart $otherCart): void
    {
        foreach ($otherCart->items as $item) {
            $this->addItem($item->product_id, $item->quantity, $item->options ?? []);
        }
        
        foreach ($otherCart->coupons as $coupon) {
            if (!$this->coupons()->where('coupon_id', $coupon->id)->exists()) {
                $this->coupons()->attach($coupon->id);
            }
        }
        
        $this->updateTotals();
        $otherCart->delete();
    }

    // Static Methods
    public static function getOrCreateForUser(int $userId): Cart
    {
        return static::firstOrCreate(
            ['user_id' => $userId, 'status' => 'active'],
            ['currency' => 'SAR']
        );
    }

    public static function getOrCreateForSession(string $sessionId): Cart
    {
        return static::firstOrCreate(
            ['session_id' => $sessionId, 'status' => 'active'],
            ['currency' => 'SAR']
        );
    }

    public static function getAbandonedCarts(int $hours = 24): \Illuminate\Database\Eloquent\Collection
    {
        return static::abandoned($hours)->with(['user', 'items.product'])->get();
    }

    public static function getAbandonedCartsCount(int $hours = 24): int
    {
        return static::abandoned($hours)->count();
    }

    public static function getConversionRate(int $days = 30): float
    {
        $totalCarts = static::where('created_at', '>=', now()->subDays($days))->count();
        $convertedCarts = static::where('created_at', '>=', now()->subDays($days))
            ->where('status', 'converted')
            ->count();
        
        if ($totalCarts == 0) {
            return 0;
        }
        
        return round(($convertedCarts / $totalCarts) * 100, 2);
    }

    public static function getAverageCartValue(int $days = 30): float
    {
        return static::where('created_at', '>=', now()->subDays($days))
            ->where('status', 'converted')
            ->avg('total_amount') ?? 0;
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($cart) {
            // Set default values
            if (is_null($cart->status)) {
                $cart->status = 'active';
            }
            
            if (is_null($cart->currency)) {
                $cart->currency = 'SAR';
            }
            
            if (is_null($cart->subtotal)) {
                $cart->subtotal = 0;
            }
            
            if (is_null($cart->tax_amount)) {
                $cart->tax_amount = 0;
            }
            
            if (is_null($cart->shipping_cost)) {
                $cart->shipping_cost = 0;
            }
            
            if (is_null($cart->discount_amount)) {
                $cart->discount_amount = 0;
            }
            
            if (is_null($cart->total_amount)) {
                $cart->total_amount = 0;
            }
        });

        static::created(function ($cart) {
            // Clear cache
            Cache::forget("user_cart_{$cart->user_id}");
            Cache::forget("session_cart_{$cart->session_id}");
        });

        static::updated(function ($cart) {
            // Clear cache
            Cache::forget("user_cart_{$cart->user_id}");
            Cache::forget("session_cart_{$cart->session_id}");
        });

        static::deleted(function ($cart) {
            // Clear cache
            Cache::forget("user_cart_{$cart->user_id}");
            Cache::forget("session_cart_{$cart->session_id}");
        });
    }
} 