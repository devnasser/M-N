<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    /**
     * عرض صفحة إتمام الطلب
     */
    public function index()
    {
        $cartItems = Cart::getCartItems(Auth::id());
        $cartTotal = Cart::getCartTotal(Auth::id());
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart')->with('error', 'السلة فارغة');
        }

        return view('buyer.checkout', compact('cartItems', 'cartTotal'));
    }

    /**
     * معالجة إتمام الطلب
     */
    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address' => 'required|string|max:500',
            'payment_method' => 'required|in:cash,card,bank_transfer',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // التحقق من صحة السلة
            $cartItems = Cart::getCartItems(Auth::id());
            if ($cartItems->isEmpty()) {
                return back()->with('error', 'السلة فارغة');
            }

            // التحقق من توفر المخزون
            foreach ($cartItems as $item) {
                if (!$item->checkStock()) {
                    return back()->with('error', "الكمية المطلوبة من {$item->product->name} غير متوفرة في المخزون");
                }
            }

            // إنشاء الطلب
            $order = Order::create([
                'user_id' => Auth::id(),
                'total_amount' => Cart::getCartTotal(Auth::id()),
                'shipping_address' => $request->shipping_address,
                'payment_method' => $request->payment_method,
                'phone' => $request->phone,
                'notes' => $request->notes,
                'status' => 'pending'
            ]);

            // إضافة عناصر الطلب
            foreach ($cartItems as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total_price' => $item->total_price,
                    'notes' => $item->notes
                ]);

                // تحديث المخزون
                $item->product->decrement('stock', $item->quantity);
            }

            // تفريغ السلة
            Cart::clearCart(Auth::id());

            return redirect()->route('buyer.orders')
                ->with('success', 'تم إنشاء الطلب بنجاح! رقم الطلب: ' . $order->id);

        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء إنشاء الطلب');
        }
    }

    /**
     * عرض تأكيد الطلب
     */
    public function confirm($orderId)
    {
        $order = Order::where('user_id', Auth::id())
            ->with(['items.product', 'user'])
            ->findOrFail($orderId);

        return view('buyer.order-confirmation', compact('order'));
    }
} 