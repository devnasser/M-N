<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'price',
        'total_price',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * علاقة مع المستخدم
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * علاقة مع المنتج
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * الحصول على إجمالي السلة للمستخدم
     */
    public static function getCartTotal($userId): float
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->sum('total_price');
    }

    /**
     * الحصول على عدد العناصر في السلة
     */
    public static function getCartCount($userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->sum('quantity');
    }

    /**
     * الحصول على محتويات السلة
     */
    public static function getCartItems($userId)
    {
        return self::with(['product.images', 'product.category', 'product.shop'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * إضافة منتج للسلة
     */
    public static function addToCart($userId, $productId, $quantity = 1, $notes = null): bool
    {
        $product = Product::findOrFail($productId);
        
        // التحقق من وجود المنتج في السلة
        $existingItem = self::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->first();

        if ($existingItem) {
            // تحديث الكمية والسعر
            $existingItem->quantity += $quantity;
            $existingItem->total_price = $existingItem->quantity * $product->price;
            $existingItem->notes = $notes;
            return $existingItem->save();
        }

        // إضافة عنصر جديد
        return self::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $product->price,
            'total_price' => $product->price * $quantity,
            'notes' => $notes,
            'is_active' => true
        ]);
    }

    /**
     * تحديث كمية المنتج في السلة
     */
    public static function updateQuantity($cartId, $quantity): bool
    {
        $cartItem = self::findOrFail($cartId);
        $cartItem->quantity = $quantity;
        $cartItem->total_price = $cartItem->price * $quantity;
        return $cartItem->save();
    }

    /**
     * حذف منتج من السلة
     */
    public static function removeFromCart($cartId): bool
    {
        $cartItem = self::findOrFail($cartId);
        return $cartItem->delete();
    }

    /**
     * تفريغ السلة
     */
    public static function clearCart($userId): bool
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->delete();
    }

    /**
     * تحويل السلة إلى طلب
     */
    public static function convertToOrder($userId, $shippingAddress = null, $paymentMethod = 'cash')
    {
        $cartItems = self::getCartItems($userId);
        
        if ($cartItems->isEmpty()) {
            throw new \Exception('السلة فارغة');
        }

        // إنشاء الطلب
        $order = Order::create([
            'user_id' => $userId,
            'total_amount' => self::getCartTotal($userId),
            'shipping_address' => $shippingAddress,
            'payment_method' => $paymentMethod,
            'status' => 'pending'
        ]);

        // إضافة عناصر الطلب
        foreach ($cartItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total_price' => $item->total_price,
                'notes' => $item->notes
            ]);
        }

        // تفريغ السلة
        self::clearCart($userId);

        return $order;
    }

    /**
     * التحقق من توفر المخزون
     */
    public function checkStock(): bool
    {
        return $this->product->stock >= $this->quantity;
    }

    /**
     * الحصول على معلومات المنتج مع الصور
     */
    public function getProductWithImages()
    {
        return $this->product->load(['images', 'category', 'shop']);
    }
} 