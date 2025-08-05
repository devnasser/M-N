@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Hero Section -->
    <div class="row bg-primary text-white py-5">
        <div class="col-12 text-center">
            <h1 class="display-4 mb-3">🚗 منصة قطع غيار السيارات الذكية</h1>
            <p class="lead">نسخة تجريبية كاملة - نظام متكامل للتجارة الإلكترونية</p>
            <div class="mt-4">
                <span class="badge bg-success fs-6 me-2">نسبة الإكتمال: 57%</span>
                <span class="badge bg-info fs-6 me-2">Laravel 12</span>
                <span class="badge bg-warning fs-6">Livewire 3</span>
            </div>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="row py-4 bg-light">
        <div class="col-12">
            <div class="row text-center">
                <div class="col-md-3 mb-3">
                    <div class="card border-0 bg-white shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-users fa-2x text-primary mb-2"></i>
                            <h4 class="text-primary">{{ \App\Models\User::count() }}</h4>
                            <p class="text-muted mb-0">مستخدم</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-0 bg-white shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-box fa-2x text-success mb-2"></i>
                            <h4 class="text-success">{{ \App\Models\Product::count() }}</h4>
                            <p class="text-muted mb-0">منتج</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-0 bg-white shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-store fa-2x text-info mb-2"></i>
                            <h4 class="text-info">{{ \App\Models\Shop::count() }}</h4>
                            <p class="text-muted mb-0">متجر</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-0 bg-white shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-shopping-cart fa-2x text-warning mb-2"></i>
                            <h4 class="text-warning">{{ \App\Models\Order::count() }}</h4>
                            <p class="text-muted mb-0">طلب</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الميزات المكتملة -->
    <div class="row py-5">
        <div class="col-12">
            <h2 class="text-center mb-5">✅ الميزات المكتملة</h2>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-success">
                        <div class="card-body text-center">
                            <i class="fas fa-database fa-3x text-success mb-3"></i>
                            <h5 class="card-title">قاعدة البيانات</h5>
                            <p class="card-text">12 جدول مكتمل مع العلاقات والبيانات التجريبية</p>
                            <span class="badge bg-success">95% مكتملة</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-users-cog fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">نظام المستخدمين</h5>
                            <p class="card-text">5 أدوار مختلفة مع نظام صلاحيات متقدم</p>
                            <span class="badge bg-primary">80% مكتملة</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-info">
                        <div class="card-body text-center">
                            <i class="fas fa-images fa-3x text-info mb-3"></i>
                            <h5 class="card-title">نظام الصور</h5>
                            <p class="card-text">رفع متعدد للصور مع إنشاء نسخ مصغرة</p>
                            <span class="badge bg-info">85% مكتملة</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-search fa-3x text-warning mb-3"></i>
                            <h5 class="card-title">البحث والتصفية</h5>
                            <p class="card-text">بحث متقدم مع فلاتر متعددة وترتيب</p>
                            <span class="badge bg-warning">60% مكتملة</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-danger">
                        <div class="card-body text-center">
                            <i class="fas fa-heart fa-3x text-danger mb-3"></i>
                            <h5 class="card-title">نظام المفضلة</h5>
                            <p class="card-text">إضافة وإزالة المنتجات من المفضلة</p>
                            <span class="badge bg-danger">100% مكتملة</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-secondary">
                        <div class="card-body text-center">
                            <i class="fas fa-tachometer-alt fa-3x text-secondary mb-3"></i>
                            <h5 class="card-title">لوحات التحكم</h5>
                            <p class="card-text">لوحات تحكم مخصصة لكل دور</p>
                            <span class="badge bg-secondary">70% مكتملة</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- روابط سريعة -->
    <div class="row py-5 bg-light">
        <div class="col-12">
            <h2 class="text-center mb-5">🔗 روابط سريعة للنظام</h2>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-home fa-2x text-primary mb-3"></i>
                            <h5>الصفحة الرئيسية</h5>
                            <a href="{{ route('home') }}" class="btn btn-primary">عرض</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-search fa-2x text-success mb-3"></i>
                            <h5>البحث المتقدم</h5>
                            <a href="{{ route('search') }}" class="btn btn-success">عرض</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-sign-in-alt fa-2x text-info mb-3"></i>
                            <h5>تسجيل الدخول</h5>
                            <a href="{{ route('login') }}" class="btn btn-info">عرض</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-user-plus fa-2x text-warning mb-3"></i>
                            <h5>التسجيل</h5>
                            <a href="{{ route('register') }}" class="btn btn-warning">عرض</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- لوحات التحكم -->
    <div class="row py-5">
        <div class="col-12">
            <h2 class="text-center mb-5">🎛️ لوحات التحكم</h2>
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-shield"></i> لوحة تحكم المدير</h5>
                        </div>
                        <div class="card-body">
                            <p>إدارة كاملة للنظام والمستخدمين والمنتجات</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> إحصائيات شاملة</li>
                                <li><i class="fas fa-check text-success"></i> إدارة المستخدمين</li>
                                <li><i class="fas fa-check text-success"></i> إدارة المنتجات</li>
                                <li><i class="fas fa-check text-success"></i> إدارة الطلبات</li>
                            </ul>
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">دخول</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-shopping-bag"></i> لوحة تحكم المتجر</h5>
                        </div>
                        <div class="card-body">
                            <p>إدارة المتجر والمنتجات والطلبات</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> إدارة المنتجات</li>
                                <li><i class="fas fa-check text-success"></i> إدارة الطلبات</li>
                                <li><i class="fas fa-check text-success"></i> الإحصائيات</li>
                                <li><i class="fas fa-check text-success"></i> إعدادات المتجر</li>
                            </ul>
                            <a href="{{ route('shop.dashboard') }}" class="btn btn-success">دخول</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-user"></i> لوحة تحكم المشتري</h5>
                        </div>
                        <div class="card-body">
                            <p>إدارة الطلبات والمفضلة والملف الشخصي</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> الطلبات</li>
                                <li><i class="fas fa-check text-success"></i> المفضلة</li>
                                <li><i class="fas fa-check text-success"></i> الملف الشخصي</li>
                                <li><i class="fas fa-check text-success"></i> التقييمات</li>
                            </ul>
                            <a href="{{ route('buyer.dashboard') }}" class="btn btn-info">دخول</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الميزات قيد التطوير -->
    <div class="row py-5 bg-warning bg-opacity-10">
        <div class="col-12">
            <h2 class="text-center mb-5">🔄 الميزات قيد التطوير</h2>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-shopping-cart fa-2x text-warning mb-3"></i>
                            <h6>سلة التسوق</h6>
                            <span class="badge bg-warning">قيد التطوير</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-credit-card fa-2x text-warning mb-3"></i>
                            <h6>نظام الدفع</h6>
                            <span class="badge bg-warning">قيد التطوير</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-truck fa-2x text-warning mb-3"></i>
                            <h6>نظام التوصيل</h6>
                            <span class="badge bg-warning">قيد التطوير</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-bell fa-2x text-warning mb-3"></i>
                            <h6>نظام الإشعارات</h6>
                            <span class="badge bg-warning">قيد التطوير</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- معلومات تقنية -->
    <div class="row py-5">
        <div class="col-12">
            <h2 class="text-center mb-5">⚙️ المعلومات التقنية</h2>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>🛠️ التقنيات المستخدمة</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Laravel Framework</span>
                                    <span class="badge bg-primary">v12.0</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>PHP</span>
                                    <span class="badge bg-success">v8.2+</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>SQLite Database</span>
                                    <span class="badge bg-info">v3.x</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Livewire</span>
                                    <span class="badge bg-warning">v3.0</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Bootstrap</span>
                                    <span class="badge bg-secondary">v5.3</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>📊 إحصائيات النظام</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>إجمالي الملفات</span>
                                    <span class="badge bg-primary">{{ count(glob('app/**/*.php')) + count(glob('resources/views/**/*.blade.php')) }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>النماذج (Models)</span>
                                    <span class="badge bg-success">{{ count(glob('app/Models/*.php')) }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>المتحكمات (Controllers)</span>
                                    <span class="badge bg-info">{{ count(glob('app/Http/Controllers/**/*.php')) }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>الصفحات (Views)</span>
                                    <span class="badge bg-warning">{{ count(glob('resources/views/**/*.blade.php')) }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>جداول قاعدة البيانات</span>
                                    <span class="badge bg-secondary">{{ count(glob('database/migrations/*.php')) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="row bg-dark text-white py-4">
        <div class="col-12 text-center">
            <h5>🚀 منصة قطع غيار السيارات الذكية</h5>
            <p class="mb-0">نسخة تجريبية - تم التطوير بواسطة Nasser Alanazi</p>
            <small class="text-muted">dev.na@outlook.com</small>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
}

.badge {
    font-size: 0.8em;
}
</style>
@endsection 