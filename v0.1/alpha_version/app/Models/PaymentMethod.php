<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'provider',
        'card_type',
        'card_number',
        'card_holder_name',
        'expiry_month',
        'expiry_year',
        'cvv',
        'is_default',
        'is_active',
        'token',
        'metadata',
    ];

    protected $hidden = [
        'card_number',
        'cvv',
        'token',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'expiry_month' => 'integer',
        'expiry_year' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    // Helper Methods
    public function getMaskedCardNumber(): string
    {
        if (!$this->card_number) {
            return '';
        }
        
        $length = strlen($this->card_number);
        if ($length < 4) {
            return $this->card_number;
        }
        
        return str_repeat('*', $length - 4) . substr($this->card_number, -4);
    }

    public function getCardTypeIcon(): string
    {
        $icons = [
            'visa' => 'fab fa-cc-visa',
            'mastercard' => 'fab fa-cc-mastercard',
            'amex' => 'fab fa-cc-amex',
            'discover' => 'fab fa-cc-discover',
            'mada' => 'fas fa-credit-card',
            'apple_pay' => 'fab fa-apple-pay',
            'google_pay' => 'fab fa-google-pay',
            'paypal' => 'fab fa-paypal',
        ];
        
        return $icons[$this->card_type] ?? 'fas fa-credit-card';
    }

    public function getCardTypeName(): string
    {
        $names = [
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'amex' => 'American Express',
            'discover' => 'Discover',
            'mada' => 'Mada',
            'apple_pay' => 'Apple Pay',
            'google_pay' => 'Google Pay',
            'paypal' => 'PayPal',
        ];
        
        return $names[$this->card_type] ?? 'Unknown';
    }

    public function getProviderName(): string
    {
        $providers = [
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'mada' => 'Mada',
            'stc_pay' => 'STC Pay',
            'apple_pay' => 'Apple Pay',
            'google_pay' => 'Google Pay',
        ];
        
        return $providers[$this->provider] ?? 'Unknown';
    }

    public function getExpiryDate(): string
    {
        if (!$this->expiry_month || !$this->expiry_year) {
            return '';
        }
        
        return sprintf('%02d/%d', $this->expiry_month, $this->expiry_year);
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_month || !$this->expiry_year) {
            return false;
        }
        
        $expiryDate = \Carbon\Carbon::createFromDate($this->expiry_year, $this->expiry_month, 1);
        return $expiryDate->endOfMonth()->isPast();
    }

    public function getDaysUntilExpiry(): int
    {
        if (!$this->expiry_month || !$this->expiry_year) {
            return -1;
        }
        
        $expiryDate = \Carbon\Carbon::createFromDate($this->expiry_year, $this->expiry_month, 1)->endOfMonth();
        return now()->diffInDays($expiryDate, false);
    }

    public function getExpiryStatus(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }
        
        $daysUntilExpiry = $this->getDaysUntilExpiry();
        
        if ($daysUntilExpiry <= 30) {
            return 'expiring_soon';
        }
        
        return 'valid';
    }

    public function getExpiryBadge(): string
    {
        $status = $this->getExpiryStatus();
        $badges = [
            'expired' => '<span class="badge bg-danger">منتهي الصلاحية</span>',
            'expiring_soon' => '<span class="badge bg-warning">ينتهي قريباً</span>',
            'valid' => '<span class="badge bg-success">صالح</span>',
        ];
        
        return $badges[$status] ?? $badges['valid'];
    }

    public function getStatusBadge(): string
    {
        if (!$this->is_active) {
            return '<span class="badge bg-secondary">غير نشط</span>';
        }
        
        if ($this->is_default) {
            return '<span class="badge bg-primary">افتراضي</span>';
        }
        
        return '<span class="badge bg-success">نشط</span>';
    }

    public function getTypeBadge(): string
    {
        $badges = [
            'credit_card' => '<span class="badge bg-info">بطاقة ائتمان</span>',
            'debit_card' => '<span class="badge bg-warning">بطاقة مدى</span>',
            'digital_wallet' => '<span class="badge bg-success">محفظة رقمية</span>',
            'bank_transfer' => '<span class="badge bg-primary">تحويل بنكي</span>',
        ];
        
        return $badges[$this->type] ?? '<span class="badge bg-secondary">' . $this->type . '</span>';
    }

    public function getDisplayName(): string
    {
        if ($this->type === 'credit_card' || $this->type === 'debit_card') {
            return $this->getCardTypeName() . ' •••• ' . substr($this->card_number, -4);
        }
        
        return $this->getProviderName();
    }

    public function getFullDisplayName(): string
    {
        if ($this->type === 'credit_card' || $this->type === 'debit_card') {
            return $this->card_holder_name . ' - ' . $this->getCardTypeName() . ' •••• ' . substr($this->card_number, -4);
        }
        
        return $this->getProviderName();
    }

    // Business Logic Methods
    public function makeDefault(): void
    {
        // Remove default from other payment methods
        $this->user->paymentMethods()
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
        
        // Make this payment method default
        $this->update(['is_default' => true]);
    }

    public function canBeUsed(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function getSecurityLevel(): string
    {
        if ($this->type === 'digital_wallet') {
            return 'high';
        }
        
        if ($this->type === 'credit_card') {
            return 'medium';
        }
        
        return 'standard';
    }

    // Validation Methods
    public function isValidCardNumber(): bool
    {
        if (!$this->card_number) {
            return false;
        }
        
        // Luhn algorithm for card number validation
        $number = preg_replace('/\D/', '', $this->card_number);
        $sum = 0;
        $length = strlen($number);
        
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = intval($number[$i]);
            
            if (($length - $i) % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
        }
        
        return $sum % 10 === 0;
    }

    public function isValidExpiryDate(): bool
    {
        if (!$this->expiry_month || !$this->expiry_year) {
            return false;
        }
        
        $expiryDate = \Carbon\Carbon::createFromDate($this->expiry_year, $this->expiry_month, 1)->endOfMonth();
        return $expiryDate->isFuture();
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($paymentMethod) {
            // Set default values
            if (is_null($paymentMethod->is_active)) {
                $paymentMethod->is_active = true;
            }
        });

        static::created(function ($paymentMethod) {
            // If this is the first payment method, make it default
            if ($paymentMethod->user->paymentMethods()->count() === 1) {
                $paymentMethod->makeDefault();
            }
        });

        static::updated(function ($paymentMethod) {
            // If this payment method is now default, remove default from others
            if ($paymentMethod->is_default) {
                $paymentMethod->user->paymentMethods()
                    ->where('id', '!=', $paymentMethod->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}