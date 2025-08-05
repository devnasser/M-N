@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-success text-white text-center">
                <h4 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    إنشاء حساب جديد
                </h4>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">
                                <i class="fas fa-user me-1"></i>
                                الاسم الكامل
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>
                                البريد الإلكتروني
                            </label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone me-1"></i>
                                رقم الهاتف
                            </label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone') }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-tag me-1"></i>
                                نوع الحساب
                            </label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="">اختر نوع الحساب</option>
                                <option value="buyer" {{ old('role') == 'buyer' ? 'selected' : '' }}>
                                    <i class="fas fa-shopping-cart me-1"></i>
                                    مشتري
                                </option>
                                <option value="shop" {{ old('role') == 'shop' ? 'selected' : '' }}>
                                    <i class="fas fa-store me-1"></i>
                                    صاحب متجر
                                </option>
                                <option value="driver" {{ old('role') == 'driver' ? 'selected' : '' }}>
                                    <i class="fas fa-truck me-1"></i>
                                    موصل
                                </option>
                                <option value="technician" {{ old('role') == 'technician' ? 'selected' : '' }}>
                                    <i class="fas fa-tools me-1"></i>
                                    فني
                                </option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>
                                كلمة المرور
                            </label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">
                                <i class="fas fa-lock me-1"></i>
                                تأكيد كلمة المرور
                            </label>
                            <input type="password" class="form-control" 
                                   id="password_confirmation" name="password_confirmation" required>
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            أوافق على <a href="#" class="text-primary">الشروط والأحكام</a> و <a href="#" class="text-primary">سياسة الخصوصية</a>
                        </label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            إنشاء الحساب
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <p class="mb-2">لديك حساب بالفعل؟</p>
                    <a href="{{ route('login') }}" class="btn btn-outline-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        تسجيل الدخول
                    </a>
                </div>
            </div>
        </div>

        <!-- Account Types Info -->
        <div class="row mt-4">
            <div class="col-12">
                <h5 class="text-center mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    أنواع الحسابات
                </h5>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                        <h6 class="card-title">مشتري</h6>
                        <p class="card-text small">شراء قطع غيار السيارات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-store fa-2x text-success mb-2"></i>
                        <h6 class="card-title">صاحب متجر</h6>
                        <p class="card-text small">بيع قطع غيار السيارات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-truck fa-2x text-warning mb-2"></i>
                        <h6 class="card-title">موصل</h6>
                        <p class="card-text small">توصيل الطلبات للعملاء</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-tools fa-2x text-info mb-2"></i>
                        <h6 class="card-title">فني</h6>
                        <p class="card-text small">صيانة السيارات</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 