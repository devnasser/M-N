<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'retry_count',
        'max_retries',
        'priority',
        'channel',
        'status',
        'metadata',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'priority' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    public function scopeByPriority(Builder $query, int $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->where('priority', '>=', 8);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function canRetry(): bool
    {
        return $this->isFailed() && $this->retry_count < $this->max_retries;
    }

    public function getTypeName(): string
    {
        $types = [
            'order_created' => 'طلب جديد',
            'order_confirmed' => 'تأكيد الطلب',
            'order_shipped' => 'شحن الطلب',
            'order_delivered' => 'تسليم الطلب',
            'order_cancelled' => 'إلغاء الطلب',
            'payment_success' => 'نجح الدفع',
            'payment_failed' => 'فشل الدفع',
            'appointment_created' => 'موعد جديد',
            'appointment_confirmed' => 'تأكيد الموعد',
            'appointment_reminder' => 'تذكير بالموعد',
            'appointment_cancelled' => 'إلغاء الموعد',
            'product_back_in_stock' => 'توفر المنتج',
            'price_drop' => 'انخفاض السعر',
            'new_product' => 'منتج جديد',
            'promotion' => 'عرض ترويجي',
            'system_maintenance' => 'صيانة النظام',
            'security_alert' => 'تنبيه أمني',
            'welcome' => 'ترحيب',
            'verification' => 'تحقق',
            'password_reset' => 'إعادة تعيين كلمة المرور',
        ];
        
        return $types[$this->type] ?? $this->type;
    }

    public function getTypeIcon(): string
    {
        $icons = [
            'order_created' => 'fas fa-shopping-cart',
            'order_confirmed' => 'fas fa-check-circle',
            'order_shipped' => 'fas fa-truck',
            'order_delivered' => 'fas fa-box-open',
            'order_cancelled' => 'fas fa-times-circle',
            'payment_success' => 'fas fa-credit-card',
            'payment_failed' => 'fas fa-exclamation-triangle',
            'appointment_created' => 'fas fa-calendar-plus',
            'appointment_confirmed' => 'fas fa-calendar-check',
            'appointment_reminder' => 'fas fa-bell',
            'appointment_cancelled' => 'fas fa-calendar-times',
            'product_back_in_stock' => 'fas fa-box',
            'price_drop' => 'fas fa-tags',
            'new_product' => 'fas fa-star',
            'promotion' => 'fas fa-gift',
            'system_maintenance' => 'fas fa-tools',
            'security_alert' => 'fas fa-shield-alt',
            'welcome' => 'fas fa-hand-wave',
            'verification' => 'fas fa-user-check',
            'password_reset' => 'fas fa-key',
        ];
        
        return $icons[$this->type] ?? 'fas fa-bell';
    }

    public function getChannelName(): string
    {
        $channels = [
            'database' => 'قاعدة البيانات',
            'mail' => 'البريد الإلكتروني',
            'sms' => 'رسالة نصية',
            'push' => 'إشعار فوري',
            'slack' => 'Slack',
            'telegram' => 'Telegram',
        ];
        
        return $channels[$this->channel] ?? $this->channel;
    }

    public function getChannelIcon(): string
    {
        $icons = [
            'database' => 'fas fa-database',
            'mail' => 'fas fa-envelope',
            'sms' => 'fas fa-mobile-alt',
            'push' => 'fas fa-bell',
            'slack' => 'fab fa-slack',
            'telegram' => 'fab fa-telegram',
        ];
        
        return $icons[$this->channel] ?? 'fas fa-bell';
    }

    public function getPriorityName(): string
    {
        $priorities = [
            1 => 'منخفض جداً',
            2 => 'منخفض',
            3 => 'عادي',
            4 => 'متوسط',
            5 => 'متوسط عالي',
            6 => 'عالي',
            7 => 'عالي جداً',
            8 => 'حرج',
            9 => 'حرج جداً',
            10 => 'طارئ',
        ];
        
        return $priorities[$this->priority] ?? 'غير محدد';
    }

    public function getPriorityBadge(): string
    {
        $badges = [
            1 => '<span class="badge bg-secondary">منخفض جداً</span>',
            2 => '<span class="badge bg-info">منخفض</span>',
            3 => '<span class="badge bg-primary">عادي</span>',
            4 => '<span class="badge bg-warning">متوسط</span>',
            5 => '<span class="badge bg-orange">متوسط عالي</span>',
            6 => '<span class="badge bg-danger">عالي</span>',
            7 => '<span class="badge bg-dark">عالي جداً</span>',
            8 => '<span class="badge bg-danger">حرج</span>',
            9 => '<span class="badge bg-danger">حرج جداً</span>',
            10 => '<span class="badge bg-danger">طارئ</span>',
        ];
        
        return $badges[$this->priority] ?? $badges[3];
    }

    public function getStatusBadge(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">في الانتظار</span>',
            'sent' => '<span class="badge bg-primary">مرسل</span>',
            'delivered' => '<span class="badge bg-success">تم التسليم</span>',
            'failed' => '<span class="badge bg-danger">فشل</span>',
            'cancelled' => '<span class="badge bg-secondary">ملغي</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge bg-secondary">' . $this->status . '</span>';
    }

    public function getReadBadge(): string
    {
        if ($this->isRead()) {
            return '<span class="badge bg-success">مقروء</span>';
        }
        return '<span class="badge bg-warning">غير مقروء</span>';
    }

    public function getFormattedCreatedAt(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getFormattedReadAt(): string
    {
        if (!$this->read_at) {
            return 'لم يقرأ';
        }
        return $this->read_at->diffForHumans();
    }

    public function getFormattedSentAt(): string
    {
        if (!$this->sent_at) {
            return 'لم يرسل';
        }
        return $this->sent_at->diffForHumans();
    }

    public function getFormattedDeliveredAt(): string
    {
        if (!$this->delivered_at) {
            return 'لم يسلم';
        }
        return $this->delivered_at->diffForHumans();
    }

    public function getRetryInfo(): string
    {
        if ($this->retry_count === 0) {
            return 'لا توجد محاولات إعادة';
        }
        
        return "محاولة {$this->retry_count} من {$this->max_retries}";
    }

    public function getDataValue(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    public function setDataValue(string $key, $value): void
    {
        $data = $this->data ?? [];
        data_set($data, $key, $value);
        $this->update(['data' => $data]);
    }

    // Business Logic Methods
    public function markAsRead(): void
    {
        if ($this->isUnread()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
        ]);
    }

    public function retry(): void
    {
        if ($this->canRetry()) {
            $this->update([
                'status' => 'pending',
                'retry_count' => $this->retry_count + 1,
                'failed_at' => null,
            ]);
        }
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function getDeliveryTime(): ?int
    {
        if (!$this->sent_at || !$this->delivered_at) {
            return null;
        }
        
        return $this->sent_at->diffInSeconds($this->delivered_at);
    }

    public function getDeliveryTimeFormatted(): string
    {
        $time = $this->getDeliveryTime();
        
        if (is_null($time)) {
            return 'غير متوفر';
        }
        
        if ($time < 60) {
            return $time . ' ثانية';
        }
        
        $minutes = floor($time / 60);
        $seconds = $time % 60;
        
        return $minutes . ' دقيقة ' . $seconds . ' ثانية';
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($notification) {
            // Set default values
            if (is_null($notification->status)) {
                $notification->status = 'pending';
            }
            
            if (is_null($notification->priority)) {
                $notification->priority = 3;
            }
            
            if (is_null($notification->retry_count)) {
                $notification->retry_count = 0;
            }
            
            if (is_null($notification->max_retries)) {
                $notification->max_retries = 3;
            }
            
            if (is_null($notification->channel)) {
                $notification->channel = 'database';
            }
        });

        static::created(function ($notification) {
            // Clear cache
            Cache::forget("user_notifications_count_{$notification->user_id}");
            Cache::forget("user_unread_notifications_count_{$notification->user_id}");
        });

        static::updated(function ($notification) {
            // Clear cache
            Cache::forget("user_notifications_count_{$notification->user_id}");
            Cache::forget("user_unread_notifications_count_{$notification->user_id}");
        });

        static::deleted(function ($notification) {
            // Clear cache
            Cache::forget("user_notifications_count_{$notification->user_id}");
            Cache::forget("user_unread_notifications_count_{$notification->user_id}");
        });
    }
}