@extends('layouts.app')

@section('title', 'سلة التسوق')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-shopping-cart text-primary me-2"></i>
                    سلة التسوق
                </h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-danger" onclick="clearCart()">
                        <i class="fas fa-trash me-1"></i>
                        تفريغ السلة
                    </button>
                    <a href="{{ route('home') }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i>
                        متابعة التسوق
                    </a>
                </div>
            </div>

            @if($cartItems->count() > 0)
                <div class="row">
                    <!-- قائمة المنتجات -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    المنتجات في السلة ({{ $cartCount }} منتج)
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                @foreach($cartItems as $item)
                                    <div class="cart-item border-bottom p-3" id="cart-item-{{ $item->id }}">
                                        <div class="row align-items-center">
                                            <!-- صورة المنتج -->
                                            <div class="col-md-2 col-4">
                                                <img src="{{ $item->product->getMainImageUrl() }}" 
                                                     alt="{{ $item->product->name }}"
                                                     class="img-fluid rounded"
                                                     style="max-height: 80px; object-fit: cover;">
                                            </div>
                                            
                                            <!-- معلومات المنتج -->
                                            <div class="col-md-4 col-8">
                                                <h6 class="mb-1">
                                                    <a href="{{ route('product', $item->product->slug) }}" 
                                                       class="text-decoration-none">
                                                        {{ $item->product->name }}
                                                    </a>
                                                </h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-store me-1"></i>
                                                    {{ $item->product->shop->name }}
                                                </small>
                                                @if($item->notes)
                                                    <div class="mt-1">
                                                        <small class="text-info">
                                                            <i class="fas fa-sticky-note me-1"></i>
                                                            {{ $item->notes }}
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <!-- السعر -->
                                            <div class="col-md-2 col-4 text-center">
                                                <div class="fw-bold text-primary">
                                                    {{ number_format($item->price, 2) }} ريال
                                                </div>
                                                <small class="text-muted">للقطعة</small>
                                            </div>
                                            
                                            <!-- الكمية -->
                                            <div class="col-md-2 col-4 text-center">
                                                <div class="input-group input-group-sm">
                                                    <button class="btn btn-outline-secondary" 
                                                            onclick="updateQuantity({{ $item->id }}, -1)">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" 
                                                           class="form-control text-center quantity-input"
                                                           value="{{ $item->quantity }}"
                                                           min="1" 
                                                           max="100"
                                                           onchange="updateQuantity({{ $item->id }}, this.value, true)">
                                                    <button class="btn btn-outline-secondary" 
                                                            onclick="updateQuantity({{ $item->id }}, 1)">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted d-block mt-1">
                                                    متوفر: {{ $item->product->stock }}
                                                </small>
                                            </div>
                                            
                                            <!-- الإجمالي -->
                                            <div class="col-md-1 col-4 text-center">
                                                <div class="fw-bold text-success">
                                                    {{ number_format($item->total_price, 2) }} ريال
                                                </div>
                                            </div>
                                            
                                            <!-- حذف -->
                                            <div class="col-md-1 col-4 text-center">
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="removeFromCart({{ $item->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <!-- ملخص الطلب -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm sticky-top" style="top: 20px;">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-receipt me-2"></i>
                                    ملخص الطلب
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>عدد المنتجات:</span>
                                    <span class="fw-bold">{{ $cartCount }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>إجمالي السلة:</span>
                                    <span class="fw-bold text-primary">{{ number_format($cartTotal, 2) }} ريال</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="h5">الإجمالي النهائي:</span>
                                    <span class="h5 text-success">{{ number_format($cartTotal, 2) }} ريال</span>
                                </div>
                                
                                <button class="btn btn-success w-100 btn-lg" onclick="proceedToCheckout()">
                                    <i class="fas fa-credit-card me-2"></i>
                                    إتمام الطلب
                                </button>
                                
                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        ضمان الجودة والاسترجاع
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- السلة فارغة -->
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-shopping-cart fa-5x text-muted"></i>
                    </div>
                    <h3 class="text-muted mb-3">السلة فارغة</h3>
                    <p class="text-muted mb-4">لم تقم بإضافة أي منتجات إلى السلة بعد</p>
                    <a href="{{ route('home') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>
                        ابدأ التسوق
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal للتأكيد -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد العملية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" id="confirmAction">تأكيد</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// تحديث الكمية
function updateQuantity(cartId, quantity, isDirectInput = false) {
    let newQuantity;
    
    if (isDirectInput) {
        newQuantity = parseInt(quantity);
    } else {
        const currentInput = document.querySelector(`#cart-item-${cartId} .quantity-input`);
        const currentQuantity = parseInt(currentInput.value);
        newQuantity = currentQuantity + parseInt(quantity);
    }
    
    if (newQuantity < 1) newQuantity = 1;
    if (newQuantity > 100) newQuantity = 100;
    
    fetch(`/cart/update-quantity`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            cart_id: cartId,
            quantity: newQuantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // تحديث العرض
            document.querySelector(`#cart-item-${cartId} .quantity-input`).value = newQuantity;
            updateCartDisplay(data);
            showToast('success', data.message);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(error => {
        showToast('error', 'حدث خطأ أثناء تحديث الكمية');
    });
}

// حذف من السلة
function removeFromCart(cartId) {
    showConfirmModal('هل أنت متأكد من حذف هذا المنتج من السلة؟', () => {
        fetch(`/cart/remove`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ cart_id: cartId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`#cart-item-${cartId}`).remove();
                updateCartDisplay(data);
                showToast('success', data.message);
                
                // إذا كانت السلة فارغة، إعادة تحميل الصفحة
                if (data.cart_count === 0) {
                    location.reload();
                }
            } else {
                showToast('error', data.message);
            }
        })
        .catch(error => {
            showToast('error', 'حدث خطأ أثناء حذف المنتج');
        });
    });
}

// تفريغ السلة
function clearCart() {
    showConfirmModal('هل أنت متأكد من تفريغ السلة بالكامل؟', () => {
        fetch(`/cart/clear`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showToast('error', data.message);
            }
        })
        .catch(error => {
            showToast('error', 'حدث خطأ أثناء تفريغ السلة');
        });
    });
}

// إتمام الطلب
function proceedToCheckout() {
    // التحقق من صحة السلة أولاً
    fetch(`/cart/validate`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.is_valid) {
            // الانتقال لصفحة إتمام الطلب
            window.location.href = '/checkout';
        } else {
            showToast('error', 'يوجد مشاكل في السلة: ' + data.errors.join(', '));
        }
    })
    .catch(error => {
        showToast('error', 'حدث خطأ أثناء التحقق من السلة');
    });
}

// تحديث عرض السلة
function updateCartDisplay(data) {
    // تحديث عدد العناصر في الهيدر
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        cartBadge.textContent = data.cart_count;
    }
    
    // تحديث الإجمالي في ملخص الطلب
    const totalElement = document.querySelector('.card-body .text-success');
    if (totalElement) {
        totalElement.textContent = data.cart_total + ' ريال';
    }
}

// عرض Modal التأكيد
function showConfirmModal(message, callback) {
    document.getElementById('confirmMessage').textContent = message;
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    
    document.getElementById('confirmAction').onclick = () => {
        modal.hide();
        callback();
    };
    
    modal.show();
}

// عرض Toast
function showToast(type, message) {
    // يمكن استخدام مكتبة Toast أو إنشاء toast بسيط
    alert(message);
}
</script>
@endpush 