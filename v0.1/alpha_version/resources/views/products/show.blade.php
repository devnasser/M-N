@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">الرئيسية</a></li>
            <li class="breadcrumb-item"><a href="{{ route('category', $product->category->slug) }}">{{ $product->category->name_ar }}</a></li>
            <li class="breadcrumb-item active">{{ $product->name_ar }}</li>
        </ol>
    </nav>

    <div class="row">
        <!-- صور المنتج -->
        <div class="col-lg-6">
            <div class="product-gallery">
                <div class="main-image mb-3">
                    <img id="mainImage" src="{{ $product->getMainImage() }}" class="img-fluid rounded" alt="{{ $product->name_ar }}">
                </div>
                
                @if($product->hasImages() && count($product->images) > 1)
                <div class="thumbnail-images">
                    <div class="row">
                        @foreach($product->images as $index => $image)
                        <div class="col-3 mb-2">
                            <img src="{{ \App\Helpers\ImageHelper::getImageUrl($image) }}" 
                                 class="img-thumbnail thumbnail-img" 
                                 onclick="changeMainImage('{{ \App\Helpers\ImageHelper::getImageUrl($image) }}')"
                                 alt="{{ $product->name_ar }}">
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- تفاصيل المنتج -->
        <div class="col-lg-6">
            <div class="product-details">
                <h1 class="h2 mb-3">{{ $product->name_ar }}</h1>
                <p class="text-muted">{{ $product->name_en }}</p>

                <!-- التقييم -->
                <div class="rating mb-3">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= $product->rating)
                            <i class="fas fa-star text-warning"></i>
                        @else
                            <i class="far fa-star text-warning"></i>
                        @endif
                    @endfor
                    <span class="ms-2">({{ $product->reviews_count }} تقييم)</span>
                </div>

                <!-- السعر -->
                <div class="price-section mb-4">
                    @if($product->isOnSale())
                        <div class="original-price text-muted text-decoration-line-through">
                            {{ $product->getFormattedPrice() }}
                        </div>
                        <div class="sale-price h3 text-danger">
                            {{ $product->getFormattedSalePrice() }}
                        </div>
                        <div class="discount-badge">
                            <span class="badge bg-danger">خصم {{ $product->getDiscountPercentage() }}%</span>
                        </div>
                    @else
                        <div class="price h3 text-primary">
                            {{ $product->getFormattedPrice() }}
                        </div>
                    @endif
                </div>

                <!-- المخزون -->
                <div class="stock-info mb-3">
                    @if($product->isInStock())
                        <span class="badge bg-success">متوفر في المخزون</span>
                        <small class="text-muted">({{ $product->stock_quantity }} قطعة متبقية)</small>
                    @else
                        <span class="badge bg-danger">نفذ المخزون</span>
                    @endif
                </div>

                <!-- الأزرار -->
                <div class="action-buttons mb-4">
                    @if($product->isInStock())
                        <button class="btn btn-primary btn-lg me-2" onclick="addToCart({{ $product->id }})">
                            <i class="fas fa-shopping-cart"></i> إضافة للسلة
                        </button>
                    @endif
                    
                    <button class="btn btn-outline-primary btn-lg me-2" onclick="toggleFavorite({{ $product->id }})">
                        <i class="fas fa-heart" id="favoriteIcon{{ $product->id }}"></i>
                        <span id="favoriteText{{ $product->id }}">
                            {{ \App\Models\Favorite::isFavorited(auth()->id(), $product->id) ? 'إزالة من المفضلة' : 'إضافة للمفضلة' }}
                        </span>
                    </button>
                </div>

                <!-- معلومات إضافية -->
                <div class="product-info">
                    <div class="row">
                        <div class="col-6">
                            <strong>SKU:</strong> {{ $product->sku }}
                        </div>
                        <div class="col-6">
                            <strong>العلامة التجارية:</strong> {{ $product->brand }}
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6">
                            <strong>المتجر:</strong> {{ $product->shop->name_ar ?? $product->shop->name_en }}
                        </div>
                        <div class="col-6">
                            <strong>المشاهدات:</strong> {{ $product->views }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- تفاصيل المنتج -->
    <div class="row mt-5">
        <div class="col-12">
            <ul class="nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">
                        الوصف
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="specifications-tab" data-bs-toggle="tab" data-bs-target="#specifications" type="button" role="tab">
                        المواصفات
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">
                        التقييمات ({{ $product->reviews_count }})
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="productTabsContent">
                <div class="tab-pane fade show active" id="description" role="tabpanel">
                    <div class="p-4">
                        <h4>الوصف</h4>
                        <p>{{ $product->description_ar }}</p>
                        <hr>
                        <h4>Description</h4>
                        <p>{{ $product->description_en }}</p>
                    </div>
                </div>

                <div class="tab-pane fade" id="specifications" role="tabpanel">
                    <div class="p-4">
                        @if($product->specifications)
                            @foreach($product->specifications as $key => $value)
                                <div class="row mb-2">
                                    <div class="col-4"><strong>{{ $key }}:</strong></div>
                                    <div class="col-8">{{ $value }}</div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted">لا توجد مواصفات متاحة</p>
                        @endif
                    </div>
                </div>

                <div class="tab-pane fade" id="reviews" role="tabpanel">
                    <div class="p-4">
                        @if($product->reviews->count() > 0)
                            @foreach($product->reviews as $review)
                                <div class="review-item border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>{{ $review->user->name }}</strong>
                                            <div class="rating">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $review->rating)
                                                        <i class="fas fa-star text-warning"></i>
                                                    @else
                                                        <i class="far fa-star text-warning"></i>
                                                    @endif
                                                @endfor
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ $review->created_at->format('Y-m-d') }}</small>
                                    </div>
                                    <p class="mt-2">{{ $review->comment }}</p>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted">لا توجد تقييمات بعد</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- المنتجات ذات الصلة -->
    @if($relatedProducts->count() > 0)
    <div class="row mt-5">
        <div class="col-12">
            <h3>منتجات ذات صلة</h3>
            <div class="row">
                @foreach($relatedProducts as $relatedProduct)
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100">
                        <img src="{{ $relatedProduct->getMainImage() }}" class="card-img-top" alt="{{ $relatedProduct->name_ar }}">
                        <div class="card-body">
                            <h6 class="card-title">{{ $relatedProduct->name_ar }}</h6>
                            <p class="card-text">{{ $relatedProduct->getFormattedPrice() }}</p>
                            <a href="{{ route('product', $relatedProduct->slug) }}" class="btn btn-primary btn-sm">عرض المنتج</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function changeMainImage(imageUrl) {
    document.getElementById('mainImage').src = imageUrl;
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
        const text = document.getElementById(`favoriteText${productId}`);
        
        if (data.isFavorited) {
            icon.className = 'fas fa-heart text-danger';
            text.textContent = 'إزالة من المفضلة';
        } else {
            icon.className = 'fas fa-heart';
            text.textContent = 'إضافة للمفضلة';
        }
    });
}
</script>
@endsection 