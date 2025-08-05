<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $shop = Shop::where('user_id', $user->id)->first();
        
        if (!$shop) {
            return redirect()->route('shop.setup');
        }

        $stats = [
            'total_products' => Product::where('shop_id', $shop->id)->count(),
            'total_orders' => Order::where('shop_id', $shop->id)->count(),
            'pending_orders' => Order::where('shop_id', $shop->id)->where('status', 'pending')->count(),
            'total_revenue' => Order::where('shop_id', $shop->id)->where('status', 'completed')->sum('total_amount'),
        ];

        $recentOrders = Order::where('shop_id', $shop->id)
            ->with(['user', 'orderItems.product'])
            ->latest()
            ->take(10)
            ->get();

        $topProducts = Product::where('shop_id', $shop->id)
            ->orderBy('views', 'desc')
            ->take(5)
            ->get();

        return view('shop.dashboard', compact('stats', 'recentOrders', 'topProducts', 'shop'));
    }

    public function setup()
    {
        $user = auth()->user();
        $shop = Shop::where('user_id', $user->id)->first();
        
        if ($shop) {
            return redirect()->route('shop.dashboard');
        }

        return view('shop.setup');
    }

    public function products()
    {
        $user = auth()->user();
        $shop = Shop::where('user_id', $user->id)->first();
        
        if (!$shop) {
            return redirect()->route('shop.setup');
        }

        $products = Product::where('shop_id', $shop->id)
            ->with('category')
            ->paginate(20);

        return view('shop.products', compact('products', 'shop'));
    }

    public function orders()
    {
        $user = auth()->user();
        $shop = Shop::where('user_id', $user->id)->first();
        
        if (!$shop) {
            return redirect()->route('shop.setup');
        }

        $orders = Order::where('shop_id', $shop->id)
            ->with(['user', 'driver', 'orderItems.product'])
            ->latest()
            ->paginate(20);

        return view('shop.orders', compact('orders', 'shop'));
    }

    public function analytics()
    {
        $user = auth()->user();
        $shop = Shop::where('user_id', $user->id)->first();
        
        if (!$shop) {
            return redirect()->route('shop.setup');
        }

        // إحصائيات المتجر
        $monthlySales = Order::where('shop_id', $shop->id)
            ->where('status', 'completed')
            ->selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->get();

        $topProducts = Product::where('shop_id', $shop->id)
            ->with('category')
            ->orderBy('views', 'desc')
            ->take(10)
            ->get();

        return view('shop.analytics', compact('monthlySales', 'topProducts', 'shop'));
    }
} 