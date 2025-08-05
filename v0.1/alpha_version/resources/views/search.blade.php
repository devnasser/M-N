@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <!-- فلاتر البحث -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🔍 فلاتر البحث</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('search') }}" method="GET">
                        @if($query)
                            <input type="hidden" name="q" value="{{ $query }}">
                        @endif
                        
                        <!-- البحث -->
                        <div class="mb-3">
                            <label class="form-label">كلمة البحث</label>
                            <input type="text" name="q" class="form-control" value="{{ $query }}" placeholder="ابحث عن منتج...">
                        </div>

                        <!-- الفئة -->
                        <div class="mb-3">
                            <label class="form-label">الفئة</label>
                            <select name="category" class="form-select">
                                <option value="">جميع الفئات</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name_ar }}
                                    </option>
                                    @foreach($category->children as $child)
                                        <option value="{{ $child->id }}" {{ request('category') == $child->id ? 'selected' : '' }}>
                                            -- {{ $child->name_ar }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        <!-- العلامة التجارية -->
                        <div class="mb-3">
                            <label class="form-label">العلامة التجارية</label>
                            <select name="brand" class="form-select">
                                <option value="">جميع العلامات</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand }}" {{ request('brand') == $brand ? 'selected' : '' }}>
                                        {{ $brand }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- نطاق السعر -->
                        <div class="mb-3">
                            <label class="form-label">نطاق السعر</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control" placeholder="من" value="{{ request('min_price') }}">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control" placeholder="إلى" value="{{ request('max_price') }}">
                                </div>
                            </div>
                        </div>

                        <!-- الترتيب -->
                        <div class="mb-3">
                            <label class="form-label">الترتيب</label>
                            <select name="sort" class="form-select">
                                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>الأحدث</option>
                                <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>السعر: من الأقل</option>
                                <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>السعر: من الأعلى</option>
                                <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>الأعلى تقييماً</option>
                                <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>الأكثر شعبية</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">🔍 بحث</button>
                        <a href="{{ route('search') }}" class="btn btn-outline-secondary w-100 mt-2">مسح الفلاتر</a>
                    </form>
                </div>
            </div>
        </div>

        <!-- نتائج البحث -->
        <div class="col-lg-9">
            <!-- عنوان البحث -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    @if($query)
                        <h4>نتائج البحث عن: "{{ $query }}"</h4>
                    @else
                        <h4>جميع المنتجات</h4>
                    @endif
                    <p class="text-muted">{{ $products->total() }} منتج</p>
                </div>
                
                <!-- عرض النتائج -->
                <div class="d-flex align-items-center">
                    <span class="me-2">عرض:</span>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="changeView('grid')">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="changeView('list')">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- المنتجات -->
            @if($products->count() > 0)
                <div class="row" id="productsGrid">
                    @foreach($products as $product)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 product-card">
                            <div class="position-relative">
                                <img src="{{ $product->getMainImage() }}" class="card-img-top" alt="{{ $product->name_ar }}" style="height: 200px; object-fit: cover;">
                                @if($product->isOnSale())
                                    <span class="badge bg-danger position-absolute top-0 start-0 m-2">خصم {{ $product->getDiscountPercentage() }}%</span>
                                @endif
                                <button class="btn btn-sm btn-outline-primary position-absolute top-0 end-0 m-2" onclick="toggleFavorite({{ $product->id }})">
                                    <i class="fas fa-heart" id="favoriteIcon{{ $product->id }}"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title">{{ $product->name_ar }}</h6>
                                <p class="text-muted small">{{ $product->name_en }}</p>
                                
                                <div class="rating mb-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $product->rating_average)
                                            <i class="fas fa-star text-warning"></i>
                                        @else
                                            <i class="far fa-star text-warning"></i>
                                        @endif
                                    @endfor
                                    <small class="text-muted">({{ $product->rating_count }})</small>
                                </div>
                                
                                <div class="price-section mb-3">
                                    @if($product->isOnSale())
                                        <div class="original-price text-muted text-decoration-line-through">
                                            {{ $product->getFormattedPrice() }}
                                        </div>
                                        <div class="sale-price text-danger fw-bold">
                                            {{ $product->getFormattedSalePrice() }}
                                        </div>
                                    @else
                                        <div class="price text-primary fw-bold">
                                            {{ $product->getFormattedPrice() }}
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="{{ route('product', $product->slug) }}" class="btn btn-primary btn-sm">
                                        عرض المنتج
                                    </a>
                                    @if($product->isInStock())
                                        <button class="btn btn-outline-success btn-sm" onclick="addToCart({{ $product->id }})">
                                            <i class="fas fa-shopping-cart"></i> إضافة للسلة
                                        </button>
                                    @else
                                        <button class="btn btn-outline-secondary btn-sm" disabled>
                                            نفذ المخزون
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- الترقيم -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $products->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">لا توجد نتائج</h4>
                    <p class="text-muted">جرب تغيير كلمات البحث أو الفلاتر</p>
                    <a href="{{ route('home') }}" class="btn btn-primary">العودة للرئيسية</a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function changeView(type) {
    const grid = document.getElementById('productsGrid');
    const buttons = document.querySelectorAll('.btn-group .btn');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    if (type === 'list') {
        grid.className = 'row';
        grid.querySelectorAll('.col-lg-4').forEach(col => {
            col.className = 'col-12 mb-3';
        });
        grid.querySelectorAll('.product-card').forEach(card => {
            card.className = 'card product-card';
            card.querySelector('.card-body').className = 'card-body d-flex align-items-center';
        });
    } else {
        grid.className = 'row';
        grid.querySelectorAll('.col-12').forEach(col => {
            col.className = 'col-lg-4 col-md-6 mb-4';
        });
        grid.querySelectorAll('.product-card').forEach(card => {
            card.className = 'card h-100 product-card';
            card.querySelector('.card-body').className = 'card-body';
        });
    }
}

function toggleFavorite(productId) {
    fetch(`/favorites/toggle/${productId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        const icon = document.getElementById(`favoriteIcon${productId}`);
        if (data.isFavorited) {
            icon.className = 'fas fa-heart text-danger';
        } else {
            icon.className = 'fas fa-heart';
        }
    });
}

function addToCart(productId) {
    // إظهار modal لإدخال الكمية
    const quantity = prompt('أدخل الكمية المطلوبة:', '1');
    
    if (quantity === null) return; // تم إلغاء العملية
    
    const quantityNum = parseInt(quantity);
    if (isNaN(quantityNum) || quantityNum < 1) {
        alert('يرجى إدخال كمية صحيحة');
        return;
    }
    
    // إضافة للسلة
    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantityNum
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // تحديث عدد العناصر في السلة
            const cartBadge = document.getElementById('cartBadge');
            if (cartBadge) {
                cartBadge.textContent = data.cart_count;
            }
            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('حدث خطأ أثناء إضافة المنتج للسلة');
    });
}
</script>

<style>
.product-card {
    transition: transform 0.2s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn-group .btn.active {
    background-color: #0d6efd;
    color: white;
}
</style>
@endsection 