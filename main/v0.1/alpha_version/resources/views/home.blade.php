@extends('layouts.app')

@section('content')
<!-- Hero Section -->
<div class="hero-section bg-primary text-white py-5 mb-5 rounded">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-car me-2"></i>
                    منصة قطع غيار السيارات الذكية
                </h1>
                <p class="lead mb-4">
                    اكتشف أفضل قطع غيار السيارات الأصلية والبديلة مع خدمات التوصيل السريع والصيانة المتخصصة
                </p>
                <div class="d-flex gap-3">
                    <a href="{{ route('search') }}" class="btn btn-light btn-lg">
                        <i class="fas fa-search me-2"></i>
                        ابحث عن المنتجات
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>
                        إنشاء حساب
                    </a>
                </div>
            </div>
            <div class="col-md-6 text-center">
                <i class="fas fa-car-side" style="font-size: 8rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
</div>

<!-- Search Section -->
<div class="search-section mb-5">
    <div class="container">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('search') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="q" class="form-control form-control-lg" placeholder="ابحث عن قطع غيار..." value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select form-select-lg">
                            <option value="">جميع التصنيفات</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @foreach($category->children as $child)
                                    <option value="{{ $child->id }}" {{ request('category') == $child->id ? 'selected' : '' }}>
                                        -- {{ $child->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="brand" class="form-select form-select-lg">
                            <option value="">جميع الماركات</option>
                            <option value="تويوتا" {{ request('brand') == 'تويوتا' ? 'selected' : '' }}>تويوتا</option>
                            <option value="هوندا" {{ request('brand') == 'هوندا' ? 'selected' : '' }}>هوندا</option>
                            <option value="نيسان" {{ request('brand') == 'نيسان' ? 'selected' : '' }}>نيسان</option>
                            <option value="فورد" {{ request('brand') == 'فورد' ? 'selected' : '' }}>فورد</option>
                            <option value="BMW" {{ request('brand') == 'BMW' ? 'selected' : '' }}>BMW</option>
                            <option value="مرسيدس" {{ request('brand') == 'مرسيدس' ? 'selected' : '' }}>مرسيدس</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Categories Section -->
<div class="categories-section mb-5">
    <div class="container">
        <h2 class="text-center mb-4">
            <i class="fas fa-th-large me-2"></i>
            تصنيفات المنتجات
        </h2>
        <div class="row">
            @foreach($categories as $category)
            <div class="col-md-4 col-lg-3 mb-3">
                <div class="card category-card h-100">
                    <div class="card-body">
                        <div class="category-icon" style="color: {{ $category->color }}">
                            <i class="{{ $category->icon }}"></i>
                        </div>
                        <h5 class="card-title">{{ $category->name }}</h5>
                        <p class="card-text text-muted">{{ $category->description }}</p>
                        <a href="{{ route('category', $category->slug) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>
                            تصفح المنتجات
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Featured Products Section -->
@if($featuredProducts->count() > 0)
<div class="featured-products mb-5">
    <div class="container">
        <h2 class="text-center mb-4">
            <i class="fas fa-star me-2"></i>
            المنتجات المميزة
        </h2>
        <div class="row">
            @foreach($featuredProducts as $product)
            <div class="col-md-3 mb-3">
                <div class="card product-card h-100">
                    <img src="{{ $product->getMainImage() }}" class="card-img-top product-image" alt="{{ $product->name }}">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">{{ $product->getLocalizedName() }}</h6>
                        <p class="card-text text-muted small">{{ $product->getLocalizedShortDescription() }}</p>
                        
                        <div class="rating-stars mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $product->rating_average)
                                    <i class="fas fa-star"></i>
                                @else
                                    <i class="far fa-star"></i>
                                @endif
                            @endfor
                            <small class="text-muted ms-1">({{ $product->rating_count }})</small>
                        </div>
                        
                        <div class="price-section mb-3">
                            @if($product->isOnSale())
                                <div class="sale-price">{{ number_format($product->sale_price, 2) }} ريال</div>
                                <div class="original-price">{{ number_format($product->price, 2) }} ريال</div>
                                <span class="badge bg-danger">{{ $product->getDiscountPercentage() }}% خصم</span>
                            @else
                                <div class="price">{{ number_format($product->price, 2) }} ريال</div>
                            @endif
                        </div>
                        
                        <div class="mt-auto">
                            <a href="{{ route('product', $product->slug) }}" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-eye me-1"></i>
                                عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- New Products Section -->
@if($newProducts->count() > 0)
<div class="new-products mb-5">
    <div class="container">
        <h2 class="text-center mb-4">
            <i class="fas fa-newspaper me-2"></i>
            أحدث المنتجات
        </h2>
        <div class="row">
            @foreach($newProducts as $product)
            <div class="col-md-3 mb-3">
                <div class="card product-card h-100">
                    <img src="{{ $product->getMainImage() }}" class="card-img-top product-image" alt="{{ $product->name }}">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">{{ $product->getLocalizedName() }}</h6>
                        <p class="card-text text-muted small">{{ $product->getLocalizedShortDescription() }}</p>
                        
                        <div class="rating-stars mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $product->rating_average)
                                    <i class="fas fa-star"></i>
                                @else
                                    <i class="far fa-star"></i>
                                @endif
                            @endfor
                            <small class="text-muted ms-1">({{ $product->rating_count }})</small>
                        </div>
                        
                        <div class="price-section mb-3">
                            @if($product->isOnSale())
                                <div class="sale-price">{{ number_format($product->sale_price, 2) }} ريال</div>
                                <div class="original-price">{{ number_format($product->price, 2) }} ريال</div>
                                <span class="badge bg-danger">{{ $product->getDiscountPercentage() }}% خصم</span>
                            @else
                                <div class="price">{{ number_format($product->price, 2) }} ريال</div>
                            @endif
                        </div>
                        
                        <div class="mt-auto">
                            <a href="{{ route('product', $product->slug) }}" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-eye me-1"></i>
                                عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- On Sale Products Section -->
@if($onSaleProducts->count() > 0)
<div class="on-sale-products mb-5">
    <div class="container">
        <h2 class="text-center mb-4">
            <i class="fas fa-tags me-2"></i>
            عروض خاصة
        </h2>
        <div class="row">
            @foreach($onSaleProducts as $product)
            <div class="col-md-3 mb-3">
                <div class="card product-card h-100 border-danger">
                    <div class="position-absolute top-0 start-0 m-2">
                        <span class="badge bg-danger">{{ $product->getDiscountPercentage() }}% خصم</span>
                    </div>
                    <img src="{{ $product->getMainImage() }}" class="card-img-top product-image" alt="{{ $product->name }}">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">{{ $product->getLocalizedName() }}</h6>
                        <p class="card-text text-muted small">{{ $product->getLocalizedShortDescription() }}</p>
                        
                        <div class="rating-stars mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $product->rating_average)
                                    <i class="fas fa-star"></i>
                                @else
                                    <i class="far fa-star"></i>
                                @endif
                            @endfor
                            <small class="text-muted ms-1">({{ $product->rating_count }})</small>
                        </div>
                        
                        <div class="price-section mb-3">
                            <div class="sale-price">{{ number_format($product->sale_price, 2) }} ريال</div>
                            <div class="original-price">{{ number_format($product->price, 2) }} ريال</div>
                        </div>
                        
                        <div class="mt-auto">
                            <a href="{{ route('product', $product->slug) }}" class="btn btn-danger btn-sm w-100">
                                <i class="fas fa-shopping-cart me-1"></i>
                                اشتر الآن
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Featured Shops Section -->
@if($featuredShops->count() > 0)
<div class="featured-shops mb-5">
    <div class="container">
        <h2 class="text-center mb-4">
            <i class="fas fa-store me-2"></i>
            المتاجر المميزة
        </h2>
        <div class="row">
            @foreach($featuredShops as $shop)
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-store fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="card-title mb-1">{{ $shop->name }}</h5>
                                <div class="rating-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $shop->rating_average)
                                            <i class="fas fa-star"></i>
                                        @else
                                            <i class="far fa-star"></i>
                                        @endif
                                    @endfor
                                    <small class="text-muted ms-1">({{ $shop->rating_count }})</small>
                                </div>
                            </div>
                        </div>
                        <p class="card-text text-muted">{{ $shop->description }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                {{ $shop->city }}
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-shopping-cart me-1"></i>
                                {{ $shop->total_orders }} طلب
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Features Section -->
<div class="features-section mb-5">
    <div class="container">
        <h2 class="text-center mb-4">
            <i class="fas fa-cogs me-2"></i>
            مميزات المنصة
        </h2>
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">منتجات أصلية</h5>
                        <p class="card-text">جميع المنتجات أصلية ومضمونة الجودة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-truck fa-3x text-success mb-3"></i>
                        <h5 class="card-title">توصيل سريع</h5>
                        <p class="card-text">خدمة توصيل سريعة وآمنة لجميع المناطق</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-tools fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">خدمة صيانة</h5>
                        <p class="card-text">فنيين متخصصين لصيانة سيارتك</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-headset fa-3x text-info mb-3"></i>
                        <h5 class="card-title">دعم 24/7</h5>
                        <p class="card-text">خدمة عملاء متاحة على مدار الساعة</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 