@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4 text-gray-800">مرحباً {{ auth()->user()->name }}</h1>
            @if($shop)
            <p class="text-muted">متجر: {{ $shop->name_ar ?? $shop->name_en }}</p>
            @endif
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">إجمالي المنتجات</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_products'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">إجمالي الطلبات</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_orders'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">الطلبات المعلقة</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending_orders'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-right-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">إجمالي الإيرادات</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_revenue'], 2) }} ريال</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الطلبات الحديثة -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">الطلبات الحديثة</h6>
                    <a href="{{ route('shop.orders') }}" class="btn btn-primary btn-sm">عرض جميع الطلبات</a>
                </div>
                <div class="card-body">
                    @if($recentOrders->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>العميل</th>
                                    <th>المنتجات</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                <tr>
                                    <td>{{ $order->order_number }}</td>
                                    <td>{{ $order->user->name }}</td>
                                    <td>{{ $order->orderItems->count() }} منتج</td>
                                    <td>{{ number_format($order->total_amount, 2) }} ريال</td>
                                    <td>
                                        <span class="badge badge-{{ $order->status == 'completed' ? 'success' : ($order->status == 'pending' ? 'warning' : 'info') }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td>{{ $order->created_at->format('Y-m-d') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-cart fa-3x text-gray-300 mb-3"></i>
                        <p class="text-gray-500">لا توجد طلبات حديثة</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- المنتجات الأكثر مشاهدة -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">المنتجات الأكثر مشاهدة</h6>
                    <a href="{{ route('shop.products') }}" class="btn btn-primary btn-sm">إدارة المنتجات</a>
                </div>
                <div class="card-body">
                    @if($topProducts->count() > 0)
                    <div class="row">
                        @foreach($topProducts as $product)
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                            <div class="card h-100">
                                <img src="{{ $product->images[0] ?? '/images/placeholder.jpg' }}" class="card-img-top" alt="{{ $product->name_ar }}">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $product->name_ar }}</h6>
                                    <p class="card-text">{{ number_format($product->price, 2) }} ريال</p>
                                    <small class="text-muted">المشاهدات: {{ $product->views }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-box fa-3x text-gray-300 mb-3"></i>
                        <p class="text-gray-500">لا توجد منتجات</p>
                        <a href="{{ route('shop.products') }}" class="btn btn-primary">إضافة منتجات</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 