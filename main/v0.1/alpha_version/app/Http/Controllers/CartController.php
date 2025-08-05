<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * عرض صفحة السلة
     */
    public function index()
    {
        $cartItems = Cart::getCartItems(Auth::id());
        $cartTotal = Cart::getCartTotal(Auth::id());
        $cartCount = Cart::getCartCount(Auth::id());

        return view('buyer.cart', compact('cartItems', 'cartTotal', 'cartCount'));
    }

    /**
     * إضافة منتج للسلة
     */
    public function add(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = Product::findOrFail($request->product_id);
            
            // التحقق من توفر المخزون
            if ($product->stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية المطلوبة غير متوفرة في المخزون'
                ], 400);
            }

            // إضافة للسلة
            Cart::addToCart(
                Auth::id(),
                $request->product_id,
                $request->quantity,
                $request->notes
            );

            $cartCount = Cart::getCartCount(Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المنتج للسلة بنجاح',
                'cart_count' => $cartCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المنتج للسلة'
            ], 500);
        }
    }

    /**
     * تحديث كمية المنتج في السلة
     */
    public function updateQuantity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required|exists:carts,id',
            'quantity' => 'required|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cartItem = Cart::findOrFail($request->cart_id);
            
            // التحقق من ملكية العنصر
            if ($cartItem->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذا العنصر'
                ], 403);
            }

            // التحقق من توفر المخزون
            if ($cartItem->product->stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية المطلوبة غير متوفرة في المخزون'
                ], 400);
            }

            // تحديث الكمية
            Cart::updateQuantity($request->cart_id, $request->quantity);

            $cartTotal = Cart::getCartTotal(Auth::id());
            $cartCount = Cart::getCartCount(Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الكمية بنجاح',
                'cart_total' => $cartTotal,
                'cart_count' => $cartCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الكمية'
            ], 500);
        }
    }

    /**
     * حذف منتج من السلة
     */
    public function remove(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required|exists:carts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cartItem = Cart::findOrFail($request->cart_id);
            
            // التحقق من ملكية العنصر
            if ($cartItem->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف هذا العنصر'
                ], 403);
            }

            // حذف العنصر
            Cart::removeFromCart($request->cart_id);

            $cartTotal = Cart::getCartTotal(Auth::id());
            $cartCount = Cart::getCartCount(Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المنتج من السلة بنجاح',
                'cart_total' => $cartTotal,
                'cart_count' => $cartCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المنتج'
            ], 500);
        }
    }

    /**
     * تفريغ السلة
     */
    public function clear(): JsonResponse
    {
        try {
            Cart::clearCart(Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'تم تفريغ السلة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تفريغ السلة'
            ], 500);
        }
    }

    /**
     * الحصول على معلومات السلة (للعرض في الهيدر)
     */
    public function getCartInfo(): JsonResponse
    {
        try {
            $cartCount = Cart::getCartCount(Auth::id());
            $cartTotal = Cart::getCartTotal(Auth::id());

            return response()->json([
                'success' => true,
                'cart_count' => $cartCount,
                'cart_total' => $cartTotal
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'cart_count' => 0,
                'cart_total' => 0
            ]);
        }
    }

    /**
     * التحقق من صحة السلة
     */
    public function validateCart(): JsonResponse
    {
        try {
            $cartItems = Cart::getCartItems(Auth::id());
            $errors = [];

            foreach ($cartItems as $item) {
                // التحقق من توفر المنتج
                if (!$item->product) {
                    $errors[] = "المنتج {$item->product->name} غير متوفر";
                    continue;
                }

                // التحقق من توفر المخزون
                if ($item->product->stock < $item->quantity) {
                    $errors[] = "الكمية المطلوبة من {$item->product->name} غير متوفرة في المخزون";
                }

                // التحقق من سعر المنتج
                if ($item->product->price != $item->price) {
                    $errors[] = "سعر {$item->product->name} قد تغير";
                }
            }

            return response()->json([
                'success' => empty($errors),
                'errors' => $errors,
                'is_valid' => empty($errors)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['حدث خطأ أثناء التحقق من السلة'],
                'is_valid' => false
            ], 500);
        }
    }
} 