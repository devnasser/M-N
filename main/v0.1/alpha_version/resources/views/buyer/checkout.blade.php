@extends('layouts.app')

@section('title', 'إتمام الطلب')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-credit-card text-success me-2"></i>
                    إتمام الطلب
                </h1>
                <a href="{{ route('cart') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i>
                    العودة للسلة
                </a>
            </div>

            <div class="row">
                <!-- نموذج إتمام الطلب -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>
                                معلومات الطلب
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('checkout.process') }}" method="POST">
                                @csrf
                                
                                <!-- معلومات الاتصال -->
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-phone me-1"></i>
                                        معلومات الاتصال
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">رقم الهاتف *</label>
                                            <input type="tel" 
                                                   class="form-control @error('phone') is-invalid @enderror" 
                                                   id="phone" 
                                                   name="phone" 
                                                   value="{{ old('phone', auth()->user()->phone) }}" 
                                                   required>
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">البريد الإلكتروني</label>
                                            <input type="email" 
                                                   class="form-control" 
                                                   value="{{ auth()->user()->email }}" 
                                                   readonly>
                                        </div>
                                    </div>
                                </div>

                                <!-- عنوان التوصيل -->
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        عنوان التوصيل
                                    </h6>
                                    <div class="form-group">
                                        <label for="shipping_address" class="form-label">العنوان التفصيلي *</label>
                                        <textarea class="form-control @error('shipping_address') is-invalid @enderror" 
                                                  id="shipping_address" 
                                                  name="shipping_address" 
                                                  rows="3" 
                                                  placeholder="أدخل العنوان التفصيلي للتوصيل..." 
                                                  required>{{ old('shipping_address') }}</textarea>
                                        @error('shipping_address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- طريقة الدفع -->
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-credit-card me-1"></i>
                                        طريقة الدفع
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input @error('payment_method') is-invalid @enderror" 
                                                       type="radio" 
                                                       name="payment_method" 
                                                       id="cash" 
                                                       value="cash" 
                                                       {{ old('payment_method') == 'cash' ? 'checked' : '' }} 
                                                       required>
                                                <label class="form-check-label" for="cash">
                                                    <i class="fas fa-money-bill-wave text-success me-1"></i>
                                                    الدفع عند الاستلام
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="radio" 
                                                       name="payment_method" 
                                                       id="card" 
                                                       value="card" 
                                                       {{ old('payment_method') == 'card' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="card">
                                                    <i class="fas fa-credit-card text-primary me-1"></i>
                                                    بطاقة ائتمان
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="radio" 
                                                       name="payment_method" 
                                                       id="bank_transfer" 
                                                       value="bank_transfer" 
                                                       {{ old('payment_method') == 'bank_transfer' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="bank_transfer">
                                                    <i class="fas fa-university text-info me-1"></i>
                                                    تحويل بنكي
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    @error('payment_method')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- ملاحظات إضافية -->
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        ملاحظات إضافية
                                    </h6>
                                    <div class="form-group">
                                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                  id="notes" 
                                                  name="notes" 
                                                  rows="3" 
                                                  placeholder="أي ملاحظات إضافية أو تعليمات خاصة...">{{ old('notes') }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- شروط وأحكام -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="terms" 
                                               required>
                                        <label class="form-check-label" for="terms">
                                            أوافق على <a href="#" class="text-primary">الشروط والأحكام</a> و 
                                            <a href="#" class="text-primary">سياسة الخصوصية</a>
                                        </label>
                                    </div>
                                </div>

                                <!-- أزرار التحكم -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-check me-2"></i>
                                        تأكيد الطلب
                                    </button>
                                    <a href="{{ route('cart') }}" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        العودة للسلة
                                    </a>
                                </div>
                            </form>
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
                            <!-- المنتجات -->
                            <div class="mb-3">
                                <h6 class="text-muted mb-2">المنتجات المطلوبة:</h6>
                                @foreach($cartItems as $item)
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ $item->product->name }}</div>
                                            <small class="text-muted">
                                                الكمية: {{ $item->quantity }} × {{ number_format($item->price, 2) }} ريال
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold">{{ number_format($item->total_price, 2) }} ريال</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <hr>

                            <!-- الإجماليات -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>إجمالي المنتجات:</span>
                                    <span>{{ number_format($cartTotal, 2) }} ريال</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>رسوم التوصيل:</span>
                                    <span class="text-success">مجاناً</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>الضريبة:</span>
                                    <span>0.00 ريال</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="h5">الإجمالي النهائي:</span>
                                    <span class="h5 text-success">{{ number_format($cartTotal, 2) }} ريال</span>
                                </div>
                            </div>

                            <!-- معلومات إضافية -->
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>معلومات مهمة:</strong><br>
                                    • سيتم التواصل معك خلال 24 ساعة<br>
                                    • مدة التوصيل: 2-5 أيام عمل<br>
                                    • ضمان استرجاع 14 يوم
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// التحقق من صحة النموذج قبل الإرسال
document.querySelector('form').addEventListener('submit', function(e) {
    const terms = document.getElementById('terms');
    if (!terms.checked) {
        e.preventDefault();
        alert('يرجى الموافقة على الشروط والأحكام');
        return false;
    }
    
    // إظهار رسالة تأكيد
    if (!confirm('هل أنت متأكد من إتمام الطلب؟')) {
        e.preventDefault();
        return false;
    }
    
    // تعطيل الزر لمنع الإرسال المتكرر
    const submitBtn = document.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري المعالجة...';
});
</script>
@endpush 