@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">المنتجات المفضلة</h2>
            
            @if($favorites->count() > 0)
                <div class="row">
                    @foreach($favorites as $product)
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card h-100 product-card">
                            <div class="position-relative">
                                <img src="{{ $product->getMainImage() }}" class="card-img-top" alt="{{ $product->name_ar }}" style="height: 200px; object-fit: cover;">
                                <button class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" 
                                        onclick="removeFromFavorites({{ $product->id }})">
                                    <i class="fas fa-times"></i>
                                </button>
                                @if($product->isOnSale())
                                    <span class="badge bg-danger position-absolute top-0 start-0 m-2">خصم {{ $product->getDiscountPercentage() }}%</span>
                                @endif
                            </div>
                            <div class="card-body">
                                <h6 class="card-title">{{ $product->name_ar }}</h6>
                                <p class="text-muted small">{{ $product->name_en }}</p>
                                
                                <div class="rating mb-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $product->rating)
                                            <i class="fas fa-star text-warning"></i>
                                        @else
                                            <i class="far fa-star text-warning"></i>
                                        @endif
                                    @endfor
                                    <small class="text-muted">({{ $product->reviews_count }})</small>
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
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $favorites->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">لا توجد منتجات في المفضلة</h4>
                    <p class="text-muted">ابدأ بتصفح المنتجات وإضافة ما يعجبك إلى المفضلة</p>
                    <a href="{{ route('home') }}" class="btn btn-primary">تصفح المنتجات</a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function removeFromFavorites(productId) {
    if (confirm('هل أنت متأكد من إزالة هذا المنتج من المفضلة؟')) {
        fetch(`/favorites/toggle/${productId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (!data.isFavorited) {
                // إزالة العنصر من الصفحة
                const productCard = document.querySelector(`[onclick="removeFromFavorites(${productId})"]`).closest('.col-lg-3');
                productCard.remove();
                
                // التحقق من وجود منتجات أخرى
                const remainingProducts = document.querySelectorAll('.product-card');
                if (remainingProducts.length === 0) {
                    location.reload(); // إعادة تحميل الصفحة لعرض رسالة "لا توجد منتجات"
                }
            }
        });
    }
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

.thumbnail-img {
    cursor: pointer;
    transition: opacity 0.2s;
}

.thumbnail-img:hover {
    opacity: 0.8;
}
</style>
@endsection 