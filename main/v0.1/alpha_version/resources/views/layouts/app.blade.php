<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'منصة قطع غيار السيارات الذكية') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        
        .product-card {
            height: 100%;
        }
        
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        
        .rating-stars {
            color: #ffc107;
        }
        
        .price {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0d6efd;
        }
        
        .sale-price {
            color: #dc3545;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #6c757d;
        }
        
        .category-card {
            text-align: center;
            padding: 1rem;
        }
        
        .category-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .footer {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .alert {
            border-radius: 0.5rem;
        }
        
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
    </style>

    @livewireStyles
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="fas fa-car me-2"></i>
                منصة قطع الغيار
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">
                            <i class="fas fa-home me-1"></i>
                            الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('search') }}">
                            <i class="fas fa-search me-1"></i>
                            البحث
                        </a>
                    </li>
                    @auth
                        @role('buyer')
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="{{ route('cart') }}">
                                <i class="fas fa-shopping-cart me-1"></i>
                                السلة
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge" 
                                      id="cartBadge">
                                    {{ \App\Models\Cart::getCartCount(auth()->id()) }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('buyer.orders') }}">
                                <i class="fas fa-list me-1"></i>
                                طلباتي
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('buyer.favorites') }}">
                                <i class="fas fa-heart me-1"></i>
                                المفضلة
                            </a>
                        </li>
                        @endrole
                    @endauth
                </ul>
                
                <ul class="navbar-nav">
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                تسجيل الدخول
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">
                                <i class="fas fa-user-plus me-1"></i>
                                إنشاء حساب
                            </a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                {{ auth()->user()->name }}
                            </a>
                            <ul class="dropdown-menu">
                                @role('admin')
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-1"></i>
                                    لوحة الإدارة
                                </a></li>
                                @endrole
                                @role('shop')
                                <li><a class="dropdown-item" href="{{ route('shop.dashboard') }}">
                                    <i class="fas fa-store me-1"></i>
                                    لوحة المتجر
                                </a></li>
                                @endrole
                                @role('driver')
                                <li><a class="dropdown-item" href="{{ route('driver.dashboard') }}">
                                    <i class="fas fa-truck me-1"></i>
                                    لوحة التوصيل
                                </a></li>
                                @endrole
                                @role('technician')
                                <li><a class="dropdown-item" href="{{ route('technician.dashboard') }}">
                                    <i class="fas fa-tools me-1"></i>
                                    لوحة الفني
                                </a></li>
                                @endrole
                                @role('buyer')
                                <li><a class="dropdown-item" href="{{ route('buyer.dashboard') }}">
                                    <i class="fas fa-user me-1"></i>
                                    لوحة المشتري
                                </a></li>
                                @endrole
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('profile') }}">
                                    <i class="fas fa-user-edit me-1"></i>
                                    الملف الشخصي
                                </a></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt me-1"></i>
                                            تسجيل الخروج
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-4">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-1"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-car me-2"></i>منصة قطع غيار السيارات الذكية</h5>
                    <p>منصة متكاملة لبيع وشراء قطع غيار السيارات مع خدمات التوصيل والصيانة</p>
                </div>
                <div class="col-md-4">
                    <h5>روابط سريعة</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('home') }}" class="text-light">الرئيسية</a></li>
                        <li><a href="{{ route('search') }}" class="text-light">البحث عن المنتجات</a></li>
                        <li><a href="{{ route('register') }}" class="text-light">إنشاء حساب</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>تواصل معنا</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-1"></i>+966 50 123 4567</li>
                        <li><i class="fas fa-envelope me-1"></i>info@autoparts.com</li>
                        <li><i class="fas fa-map-marker-alt me-1"></i>الرياض، المملكة العربية السعودية</li>
                    </ul>
                </div>
            </div>
            <hr class="my-3">
            <div class="text-center">
                <p>&copy; {{ date('Y') }} منصة قطع غيار السيارات الذكية. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @livewireScripts
</body>
</html> 