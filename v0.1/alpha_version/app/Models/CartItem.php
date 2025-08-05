<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price',
        'total_price',
        'options',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'options' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeByCart(Builder $query, int $cartId): Builder
    {
        return $query->where('cart_id', $cartId);
    }

    public function scopeByProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('cart', function ($q) {
            $q->where('status', 'active');
        });
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('product', function ($q) {
            $q->where('available_quantity', '>', 0);
        });
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->whereHas('product', function ($q) {
            $q->where('available_quantity', '<=', 0);
        });
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereHas('product', function ($q) {
            $q->whereRaw('available_quantity <= min_stock_quantity');
        });
    }

    // Helper Methods
    public function getTotalPriceFormatted(): string
    {
        return number_format($this->total_price, 2) . ' ريال';
    }

    public function getPriceFormatted(): string
    {
        return number_format($this->price, 2) . ' ريال';
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

    public function getProductWeight(): float
    {
        return $this->product->weight ?? 0;
    }

    public function getProductWeightFormatted(): string
    {
        $weight = $this->getProductWeight();
        
        if ($weight == 0) {
            return 'غير محدد';
        }
        
        return number_format($weight, 3) . ' كجم';
    }

    public function getTotalWeight(): float
    {
        return $this->getProductWeight() * $this->quantity;
    }

    public function getTotalWeightFormatted(): string
    {
        $weight = $this->getTotalWeight();
        
        if ($weight == 0) {
            return 'غير محدد';
        }
        
        return number_format($weight, 3) . ' كجم';
    }

    public function getProductDimensions(): array
    {
        return $this->product->dimensions ?? [];
    }

    public function getProductDimensionsFormatted(): string
    {
        $dimensions = $this->getProductDimensions();
        
        if (empty($dimensions)) {
            return 'غير محدد';
        }
        
        $length = $dimensions['length'] ?? 0;
        $width = $dimensions['width'] ?? 0;
        $height = $dimensions['height'] ?? 0;
        
        return $length . ' × ' . $width . ' × ' . $height . ' سم';
    }

    public function getOptions(): array
    {
        return $this->options ?? [];
    }

    public function getOptionValue(string $key, $default = null)
    {
        return data_get($this->options, $key, $default);
    }

    public function setOptionValue(string $key, $value): void
    {
        $options = $this->options ?? [];
        data_set($options, $key, $value);
        $this->update(['options' => $options]);
    }

    public function removeOption(string $key): void
    {
        $options = $this->options ?? [];
        unset($options[$key]);
        $this->update(['options' => $options]);
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

    public function getDiscountAmount(): float
    {
        $originalPrice = $this->product->price ?? 0;
        $currentPrice = $this->price;
        
        return max(0, $originalPrice - $currentPrice);
    }

    public function getDiscountAmountFormatted(): string
    {
        return number_format($this->getDiscountAmount(), 2) . ' ريال';
    }

    public function getDiscountPercentage(): float
    {
        $originalPrice = $this->product->price ?? 0;
        
        if ($originalPrice == 0) {
            return 0;
        }
        
        $discountAmount = $this->getDiscountAmount();
        return round(($discountAmount / $originalPrice) * 100, 2);
    }

    public function getDiscountPercentageFormatted(): string
    {
        return $this->getDiscountPercentage() . '%';
    }

    public function getSavingsAmount(): float
    {
        return $this->getDiscountAmount() * $this->quantity;
    }

    public function getSavingsAmountFormatted(): string
    {
        return number_format($this->getSavingsAmount(), 2) . ' ريال';
    }

    public function getStockStatus(): string
    {
        if (!$this->product) {
            return 'unknown';
        }
        
        if ($this->product->isOutOfStock()) {
            return 'out_of_stock';
        }
        
        if ($this->product->isLowStock()) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }

    public function getStockStatusBadge(): string
    {
        $status = $this->getStockStatus();
        
        $badges = [
            'in_stock' => '<span class="badge bg-success">متوفر</span>',
            'low_stock' => '<span class="badge bg-warning">مخزون منخفض</span>',
            'out_of_stock' => '<span class="badge bg-danger">نفذ المخزون</span>',
            'unknown' => '<span class="badge bg-secondary">غير محدد</span>',
        ];
        
        return $badges[$status] ?? $badges['unknown'];
    }

    public function getAvailableQuantity(): int
    {
        return $this->product->available_quantity ?? 0;
    }

    public function getAvailableQuantityFormatted(): string
    {
        $quantity = $this->getAvailableQuantity();
        
        if ($quantity == 0) {
            return 'غير متوفر';
        }
        
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

    public function getMaxQuantity(): int
    {
        $available = $this->getAvailableQuantity();
        $maxStock = $this->product->max_stock_quantity ?? 999;
        
        return min($available, $maxStock);
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

    // Business Logic Methods
    public function isInStock(): bool
    {
        return $this->getStockStatus() === 'in_stock';
    }

    public function isLowStock(): bool
    {
        return $this->getStockStatus() === 'low_stock';
    }

    public function isOutOfStock(): bool
    {
        return $this->getStockStatus() === 'out_of_stock';
    }

    public function hasDiscount(): bool
    {
        return $this->getDiscountAmount() > 0;
    }

    public function canIncreaseQuantity(): bool
    {
        return $this->quantity < $this->getMaxQuantity();
    }

    public function canDecreaseQuantity(): bool
    {
        return $this->quantity > 1;
    }

    public function isAvailable(): bool
    {
        return $this->product && $this->product->isActive() && $this->isInStock();
    }

    public function updateQuantity(int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->delete();
        }
        
        $maxQuantity = $this->getMaxQuantity();
        
        if ($quantity > $maxQuantity) {
            $quantity = $maxQuantity;
        }
        
        $this->update([
            'quantity' => $quantity,
            'total_price' => $this->price * $quantity,
        ]);
        
        return true;
    }

    public function increaseQuantity(int $amount = 1): bool
    {
        return $this->updateQuantity($this->quantity + $amount);
    }

    public function decreaseQuantity(int $amount = 1): bool
    {
        return $this->updateQuantity($this->quantity - $amount);
    }

    public function updatePrice(float $price): void
    {
        $this->update([
            'price' => $price,
            'total_price' => $price * $this->quantity,
        ]);
    }

    public function updateTotalPrice(): void
    {
        $this->update(['total_price' => $this->price * $this->quantity]);
    }

    public function addOption(string $key, $value): void
    {
        $this->setOptionValue($key, $value);
    }

    public function removeOption(string $key): void
    {
        $this->removeOption($key);
    }

    public function clearOptions(): void
    {
        $this->update(['options' => []]);
    }

    public function hasOption(string $key): bool
    {
        return array_key_exists($key, $this->getOptions());
    }

    public function getOptionKeys(): array
    {
        return array_keys($this->getOptions());
    }

    public function getOptionCount(): int
    {
        return count($this->getOptions());
    }

    public function hasOptions(): bool
    {
        return $this->getOptionCount() > 0;
    }

    public function getSubtotal(): float
    {
        return $this->total_price;
    }

    public function getSubtotalFormatted(): string
    {
        return number_format($this->getSubtotal(), 2) . ' ريال';
    }

    public function getTaxAmount(): float
    {
        return ($this->total_price * 15) / 100; // 15% VAT
    }

    public function getTaxAmountFormatted(): string
    {
        return number_format($this->getTaxAmount(), 2) . ' ريال';
    }

    public function getTotalWithTax(): float
    {
        return $this->total_price + $this->getTaxAmount();
    }

    public function getTotalWithTaxFormatted(): string
    {
        return number_format($this->getTotalWithTax(), 2) . ' ريال';
    }

    public function getProfit(): float
    {
        if (!$this->product || !$this->product->cost_price) {
            return 0;
        }
        
        $cost = $this->product->cost_price * $this->quantity;
        return $this->total_price - $cost;
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

    public function validateStock(): array
    {
        $errors = [];
        
        if (!$this->product) {
            $errors[] = 'المنتج غير موجود';
            return $errors;
        }
        
        if (!$this->product->isActive()) {
            $errors[] = 'المنتج غير متاح';
        }
        
        if ($this->isOutOfStock()) {
            $errors[] = 'المنتج نفذ من المخزون';
        }
        
        if ($this->quantity > $this->getAvailableQuantity()) {
            $errors[] = 'الكمية المطلوبة غير متوفرة. المتوفر: ' . $this->getAvailableQuantityFormatted();
        }
        
        return $errors;
    }

    public function canBeOrdered(): bool
    {
        return empty($this->validateStock());
    }

    public function duplicate(): CartItem
    {
        $newItem = $this->replicate();
        $newItem->quantity = 1;
        $newItem->total_price = $this->price;
        $newItem->save();
        
        return $newItem;
    }

    public function moveToCart(Cart $targetCart): bool
    {
        if ($this->cart_id == $targetCart->id) {
            return false;
        }
        
        $existingItem = $targetCart->items()->where('product_id', $this->product_id)->first();
        
        if ($existingItem) {
            $existingItem->increaseQuantity($this->quantity);
            $this->delete();
        } else {
            $this->update(['cart_id' => $targetCart->id]);
        }
        
        return true;
    }

    // Static Methods
    public static function getTotalItemsCount(int $cartId): int
    {
        return static::byCart($cartId)->sum('quantity');
    }

    public static function getTotalValue(int $cartId): float
    {
        return static::byCart($cartId)->sum('total_price');
    }

    public static function getTotalWeight(int $cartId): float
    {
        return static::byCart($cartId)->sum(\DB::raw('quantity * (SELECT weight FROM products WHERE products.id = cart_items.product_id)'));
    }

    public static function getOutOfStockItems(int $cartId): \Illuminate\Database\Eloquent\Collection
    {
        return static::byCart($cartId)->outOfStock()->with('product')->get();
    }

    public static function getLowStockItems(int $cartId): \Illuminate\Database\Eloquent\Collection
    {
        return static::byCart($cartId)->lowStock()->with('product')->get();
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($item) {
            // Set default values
            if (is_null($item->quantity)) {
                $item->quantity = 1;
            }
            
            if (is_null($item->price)) {
                $item->price = $item->product->getCurrentPrice() ?? 0;
            }
            
            if (is_null($item->total_price)) {
                $item->total_price = $item->price * $item->quantity;
            }
        });

        static::created(function ($item) {
            // Update cart totals
            if ($item->cart) {
                $item->cart->updateTotals();
            }
        });

        static::updated(function ($item) {
            // Update cart totals
            if ($item->cart) {
                $item->cart->updateTotals();
            }
        });

        static::deleted(function ($item) {
            // Update cart totals
            if ($item->cart) {
                $item->cart->updateTotals();
            }
        });
    }
}