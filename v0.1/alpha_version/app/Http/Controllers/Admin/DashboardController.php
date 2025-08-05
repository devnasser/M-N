<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Shop;
use App\Models\Driver;
use App\Models\Technician;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_shops' => Shop::count(),
            'total_drivers' => Driver::count(),
            'total_technicians' => Technician::count(),
            'recent_orders' => Order::with(['user', 'shop'])->latest()->take(10)->get(),
            'top_products' => Product::with('category')->orderBy('views', 'desc')->take(10)->get(),
            'recent_users' => User::latest()->take(10)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function users()
    {
        $users = User::with('roles')->paginate(20);
        return view('admin.users', compact('users'));
    }

    public function products()
    {
        $products = Product::with(['category', 'shop'])->paginate(20);
        return view('admin.products', compact('products'));
    }

    public function orders()
    {
        $orders = Order::with(['user', 'shop', 'driver'])->paginate(20);
        return view('admin.orders', compact('orders'));
    }

    public function shops()
    {
        $shops = Shop::with('user')->paginate(20);
        return view('admin.shops', compact('shops'));
    }

    public function drivers()
    {
        $drivers = Driver::with('user')->paginate(20);
        return view('admin.drivers', compact('drivers'));
    }

    public function technicians()
    {
        $technicians = Technician::with('user')->paginate(20);
        return view('admin.technicians', compact('technicians'));
    }

    public function analytics()
    {
        // إحصائيات متقدمة
        $monthlyOrders = Order::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->get();

        $topCategories = Product::with('category')
            ->selectRaw('category_id, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get();

        return view('admin.analytics', compact('monthlyOrders', 'topCategories'));
    }
} 