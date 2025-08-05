<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Favorite;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $stats = [
            'total_orders' => Order::where('user_id', $user->id)->count(),
            'pending_orders' => Order::where('user_id', $user->id)->where('status', 'pending')->count(),
            'completed_orders' => Order::where('user_id', $user->id)->where('status', 'completed')->count(),
            'total_favorites' => Favorite::where('user_id', $user->id)->count(),
        ];

        $recentOrders = Order::where('user_id', $user->id)
            ->with(['shop', 'orderItems.product'])
            ->latest()
            ->take(5)
            ->get();

        $favoriteProducts = Product::whereHas('favorites', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->take(6)->get();

        return view('buyer.dashboard', compact('stats', 'recentOrders', 'favoriteProducts'));
    }

    public function orders()
    {
        $user = auth()->user();
        $orders = Order::where('user_id', $user->id)
            ->with(['shop', 'driver', 'orderItems.product'])
            ->latest()
            ->paginate(15);

        return view('buyer.orders', compact('orders'));
    }

    public function favorites()
    {
        $user = auth()->user();
        $favorites = Product::whereHas('favorites', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['category', 'shop'])->paginate(20);

        return view('buyer.favorites', compact('favorites'));
    }

    public function profile()
    {
        $user = auth()->user();
        return view('buyer.profile', compact('user'));
    }
} 