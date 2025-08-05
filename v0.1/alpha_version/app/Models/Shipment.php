<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tracking_number',
        'carrier',
        'carrier_en',
        'service_type',
        'service_type_en',
        'status',
        'shipped_at',
        'estimated_delivery_date',
        'actual_delivery_date',
        'shipping_address_id',
        'shipping_cost',
        'insurance_amount',
        'weight',
        'weight_unit',
        'dimensions',
        'package_count',
        'tracking_url',
        'label_url',
        'notes',
        'notes_en',
        'metadata',
    ];

    protected $hidden = [
        'metadata',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'estimated_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'shipping_cost' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_RETURNED = 'returned';
    const STATUS_CANCELLED = 'cancelled';

    // Service type constants
    const SERVICE_STANDARD = 'standard';
    const SERVICE_EXPRESS = 'express';
    const SERVICE_PRIORITY = 'priority';
    const SERVICE_SAME_DAY = 'same_day';
    const SERVICE_NEXT_DAY = 'next_day';

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(UserAddress::class, 'shipping_address_id');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Scopes
    public function scopeByOrder(Builder $query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCarrier(Builder $query, $carrier)
    {
        return $query->where('carrier', $carrier);
    }

    public function scopeByServiceType(Builder $query, $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    public function scopeByTrackingNumber(Builder $query, $trackingNumber)
    {
        return $query->where('tracking_number', $trackingNumber);
    }

    public function scopePending(Builder $query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing(Builder $query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeShipped(Builder $query)
    {
        return $query->where('status', self::STATUS_SHIPPED);
    }

    public function scopeInTransit(Builder $query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    public function scopeOutForDelivery(Builder $query)
    {
        return $query->where('status', self::STATUS_OUT_FOR_DELIVERY);
    }

    public function scopeDelivered(Builder $query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeFailed(Builder $query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeReturned(Builder $query)
    {
        return $query->where('status', self::STATUS_RETURNED);
    }

    public function scopeCancelled(Builder $query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeToday(Builder $query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    public function scopeThisWeek(Builder $query)
    {
        return $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    }

    public function scopeThisMonth(Builder $query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month);
    }

    public function scopeOverdue(Builder $query)
    {
        return $query->where('estimated_delivery_date', '<', Carbon::now())
                    ->whereNotIn('status', [self::STATUS_DELIVERED, self::STATUS_RETURNED, self::STATUS_CANCELLED]);
    }

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // Helper Methods
    public function getCarrierAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->carrier) {
            return $this->carrier;
        }
        return $value ?: $this->carrier_en;
    }

    public function getServiceTypeAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->service_type) {
            return $this->service_type;
        }
        return $value ?: $this->service_type_en;
    }

    public function getNotesAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->notes) {
            return $this->notes;
        }
        return $value ?: $this->notes_en;
    }

    public function getCarrierArAttribute()
    {
        return $this->carrier;
    }

    public function getServiceTypeArAttribute()
    {
        return $this->service_type;
    }

    public function getNotesArAttribute()
    {
        return $this->notes;
    }

    public function getCarrierEnAttribute()
    {
        return $this->carrier_en;
    }

    public function getServiceTypeEnAttribute()
    {
        return $this->service_type_en;
    }

    public function getNotesEnAttribute()
    {
        return $this->notes_en;
    }

    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'Pending';
            case self::STATUS_PROCESSING:
                return 'Processing';
            case self::STATUS_SHIPPED:
                return 'Shipped';
            case self::STATUS_IN_TRANSIT:
                return 'In Transit';
            case self::STATUS_OUT_FOR_DELIVERY:
                return 'Out for Delivery';
            case self::STATUS_DELIVERED:
                return 'Delivered';
            case self::STATUS_FAILED:
                return 'Failed';
            case self::STATUS_RETURNED:
                return 'Returned';
            case self::STATUS_CANCELLED:
                return 'Cancelled';
            default:
                return 'Unknown';
        }
    }

    public function getStatusNameArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'في الانتظار';
            case self::STATUS_PROCESSING:
                return 'قيد المعالجة';
            case self::STATUS_SHIPPED:
                return 'تم الشحن';
            case self::STATUS_IN_TRANSIT:
                return 'قيد النقل';
            case self::STATUS_OUT_FOR_DELIVERY:
                return 'خارج للتوصيل';
            case self::STATUS_DELIVERED:
                return 'تم التوصيل';
            case self::STATUS_FAILED:
                return 'فشل';
            case self::STATUS_RETURNED:
                return 'تم الإرجاع';
            case self::STATUS_CANCELLED:
                return 'ملغي';
            default:
                return 'غير معروف';
        }
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return '<span class="badge bg-warning">Pending</span>';
            case self::STATUS_PROCESSING:
                return '<span class="badge bg-info">Processing</span>';
            case self::STATUS_SHIPPED:
                return '<span class="badge bg-primary">Shipped</span>';
            case self::STATUS_IN_TRANSIT:
                return '<span class="badge bg-info">In Transit</span>';
            case self::STATUS_OUT_FOR_DELIVERY:
                return '<span class="badge bg-warning">Out for Delivery</span>';
            case self::STATUS_DELIVERED:
                return '<span class="badge bg-success">Delivered</span>';
            case self::STATUS_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            case self::STATUS_RETURNED:
                return '<span class="badge bg-secondary">Returned</span>';
            case self::STATUS_CANCELLED:
                return '<span class="badge bg-dark">Cancelled</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    public function getStatusBadgeArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return '<span class="badge bg-warning">في الانتظار</span>';
            case self::STATUS_PROCESSING:
                return '<span class="badge bg-info">قيد المعالجة</span>';
            case self::STATUS_SHIPPED:
                return '<span class="badge bg-primary">تم الشحن</span>';
            case self::STATUS_IN_TRANSIT:
                return '<span class="badge bg-info">قيد النقل</span>';
            case self::STATUS_OUT_FOR_DELIVERY:
                return '<span class="badge bg-warning">خارج للتوصيل</span>';
            case self::STATUS_DELIVERED:
                return '<span class="badge bg-success">تم التوصيل</span>';
            case self::STATUS_FAILED:
                return '<span class="badge bg-danger">فشل</span>';
            case self::STATUS_RETURNED:
                return '<span class="badge bg-secondary">تم الإرجاع</span>';
            case self::STATUS_CANCELLED:
                return '<span class="badge bg-dark">ملغي</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getServiceTypeNameAttribute()
    {
        switch ($this->service_type) {
            case self::SERVICE_STANDARD:
                return 'Standard';
            case self::SERVICE_EXPRESS:
                return 'Express';
            case self::SERVICE_PRIORITY:
                return 'Priority';
            case self::SERVICE_SAME_DAY:
                return 'Same Day';
            case self::SERVICE_NEXT_DAY:
                return 'Next Day';
            default:
                return 'Standard';
        }
    }

    public function getServiceTypeNameArAttribute()
    {
        switch ($this->service_type) {
            case self::SERVICE_STANDARD:
                return 'عادي';
            case self::SERVICE_EXPRESS:
                return 'سريع';
            case self::SERVICE_PRIORITY:
                return 'مميز';
            case self::SERVICE_SAME_DAY:
                return 'نفس اليوم';
            case self::SERVICE_NEXT_DAY:
                return 'اليوم التالي';
            default:
                return 'عادي';
        }
    }

    public function getServiceTypeBadgeAttribute()
    {
        switch ($this->service_type) {
            case self::SERVICE_STANDARD:
                return '<span class="badge bg-secondary">Standard</span>';
            case self::SERVICE_EXPRESS:
                return '<span class="badge bg-warning">Express</span>';
            case self::SERVICE_PRIORITY:
                return '<span class="badge bg-info">Priority</span>';
            case self::SERVICE_SAME_DAY:
                return '<span class="badge bg-danger">Same Day</span>';
            case self::SERVICE_NEXT_DAY:
                return '<span class="badge bg-primary">Next Day</span>';
            default:
                return '<span class="badge bg-secondary">Standard</span>';
        }
    }

    public function getServiceTypeBadgeArAttribute()
    {
        switch ($this->service_type) {
            case self::SERVICE_STANDARD:
                return '<span class="badge bg-secondary">عادي</span>';
            case self::SERVICE_EXPRESS:
                return '<span class="badge bg-warning">سريع</span>';
            case self::SERVICE_PRIORITY:
                return '<span class="badge bg-info">مميز</span>';
            case self::SERVICE_SAME_DAY:
                return '<span class="badge bg-danger">نفس اليوم</span>';
            case self::SERVICE_NEXT_DAY:
                return '<span class="badge bg-primary">اليوم التالي</span>';
            default:
                return '<span class="badge bg-secondary">عادي</span>';
        }
    }

    public function getShippingCostFormattedAttribute()
    {
        return number_format($this->shipping_cost, 2) . ' SAR';
    }

    public function getShippingCostFormattedArAttribute()
    {
        return number_format($this->shipping_cost, 2) . ' ريال';
    }

    public function getInsuranceAmountFormattedAttribute()
    {
        return $this->insurance_amount ? number_format($this->insurance_amount, 2) . ' SAR' : null;
    }

    public function getInsuranceAmountFormattedArAttribute()
    {
        return $this->insurance_amount ? number_format($this->insurance_amount, 2) . ' ريال' : null;
    }

    public function getWeightFormattedAttribute()
    {
        if (!$this->weight) {
            return null;
        }
        return number_format($this->weight, 2) . ' ' . ($this->weight_unit ?: 'kg');
    }

    public function getWeightFormattedArAttribute()
    {
        return $this->getWeightFormattedAttribute();
    }

    public function getDimensionsFormattedAttribute()
    {
        if (!$this->dimensions || !isset($this->dimensions['length']) || !isset($this->dimensions['width']) || !isset($this->dimensions['height'])) {
            return null;
        }
        return $this->dimensions['length'] . ' × ' . $this->dimensions['width'] . ' × ' . $this->dimensions['height'] . ' cm';
    }

    public function getDimensionsFormattedArAttribute()
    {
        return $this->getDimensionsFormattedAttribute();
    }

    public function getShippedAtFormattedAttribute()
    {
        return $this->shipped_at ? $this->shipped_at->format('M d, Y H:i') : null;
    }

    public function getShippedAtFormattedArAttribute()
    {
        return $this->shipped_at ? $this->shipped_at->format('d M Y H:i') : null;
    }

    public function getEstimatedDeliveryDateFormattedAttribute()
    {
        return $this->estimated_delivery_date ? $this->estimated_delivery_date->format('M d, Y') : null;
    }

    public function getEstimatedDeliveryDateFormattedArAttribute()
    {
        return $this->estimated_delivery_date ? $this->estimated_delivery_date->format('d M Y') : null;
    }

    public function getActualDeliveryDateFormattedAttribute()
    {
        return $this->actual_delivery_date ? $this->actual_delivery_date->format('M d, Y H:i') : null;
    }

    public function getActualDeliveryDateFormattedArAttribute()
    {
        return $this->actual_delivery_date ? $this->actual_delivery_date->format('d M Y H:i') : null;
    }

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at->format('M d, Y H:i');
    }

    public function getCreatedAtFormattedArAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    public function getUpdatedAtFormattedAttribute()
    {
        return $this->updated_at->format('M d, Y H:i');
    }

    public function getUpdatedAtFormattedArAttribute()
    {
        return $this->updated_at->format('d M Y H:i');
    }

    public function getAgeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getAgeArAttribute()
    {
        $diff = $this->created_at->diff(Carbon::now());
        if ($diff->days > 0) {
            return $diff->days . ' يوم';
        } elseif ($diff->h > 0) {
            return $diff->h . ' ساعة';
        } elseif ($diff->i > 0) {
            return $diff->i . ' دقيقة';
        } else {
            return 'الآن';
        }
    }

    public function getDeliveryTimeAttribute()
    {
        if (!$this->shipped_at || !$this->actual_delivery_date) {
            return null;
        }
        return $this->shipped_at->diffInHours($this->actual_delivery_date);
    }

    public function getDeliveryTimeFormattedAttribute()
    {
        $hours = $this->getDeliveryTimeAttribute();
        if ($hours === null) {
            return null;
        }
        if ($hours < 24) {
            return $hours . ' hours';
        }
        $days = floor($hours / 24);
        $remainingHours = $hours % 24;
        return $days . ' days, ' . $remainingHours . ' hours';
    }

    public function getDeliveryTimeFormattedArAttribute()
    {
        $hours = $this->getDeliveryTimeAttribute();
        if ($hours === null) {
            return null;
        }
        if ($hours < 24) {
            return $hours . ' ساعة';
        }
        $days = floor($hours / 24);
        $remainingHours = $hours % 24;
        return $days . ' يوم، ' . $remainingHours . ' ساعة';
    }

    public function getDaysUntilDeliveryAttribute()
    {
        if (!$this->estimated_delivery_date) {
            return null;
        }
        return Carbon::now()->diffInDays($this->estimated_delivery_date, false);
    }

    public function getDaysUntilDeliveryFormattedAttribute()
    {
        $days = $this->getDaysUntilDeliveryAttribute();
        if ($days === null) {
            return null;
        }
        if ($days > 0) {
            return $days . ' days remaining';
        } elseif ($days < 0) {
            return abs($days) . ' days overdue';
        } else {
            return 'Due today';
        }
    }

    public function getDaysUntilDeliveryFormattedArAttribute()
    {
        $days = $this->getDaysUntilDeliveryAttribute();
        if ($days === null) {
            return null;
        }
        if ($days > 0) {
            return $days . ' يوم متبقي';
        } elseif ($days < 0) {
            return abs($days) . ' يوم متأخر';
        } else {
            return 'مستحق اليوم';
        }
    }

    // Business Logic
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing()
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isShipped()
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isInTransit()
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    public function isOutForDelivery()
    {
        return $this->status === self::STATUS_OUT_FOR_DELIVERY;
    }

    public function isDelivered()
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isReturned()
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isOverdue()
    {
        return $this->estimated_delivery_date && 
               $this->estimated_delivery_date < Carbon::now() && 
               !in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_RETURNED, self::STATUS_CANCELLED]);
    }

    public function isDeliveredOnTime()
    {
        return $this->isDelivered() && 
               $this->actual_delivery_date && 
               $this->estimated_delivery_date && 
               $this->actual_delivery_date <= $this->estimated_delivery_date;
    }

    public function isDeliveredLate()
    {
        return $this->isDelivered() && 
               $this->actual_delivery_date && 
               $this->estimated_delivery_date && 
               $this->actual_delivery_date > $this->estimated_delivery_date;
    }

    public function hasTrackingNumber()
    {
        return !empty($this->tracking_number);
    }

    public function hasTrackingUrl()
    {
        return !empty($this->tracking_url);
    }

    public function hasLabelUrl()
    {
        return !empty($this->label_url);
    }

    public function hasInsurance()
    {
        return $this->insurance_amount > 0;
    }

    public function canBeShipped()
    {
        return $this->status === self::STATUS_PENDING || $this->status === self::STATUS_PROCESSING;
    }

    public function canBeDelivered()
    {
        return $this->status === self::STATUS_OUT_FOR_DELIVERY;
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function canBeReturned()
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function ship()
    {
        $this->update([
            'status' => self::STATUS_SHIPPED,
            'shipped_at' => Carbon::now(),
        ]);
        $this->clearCache();
    }

    public function markInTransit()
    {
        $this->update(['status' => self::STATUS_IN_TRANSIT]);
        $this->clearCache();
    }

    public function markOutForDelivery()
    {
        $this->update(['status' => self::STATUS_OUT_FOR_DELIVERY]);
        $this->clearCache();
    }

    public function deliver()
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'actual_delivery_date' => Carbon::now(),
        ]);
        $this->clearCache();
    }

    public function fail($reason = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $reason,
        ]);
        $this->clearCache();
    }

    public function return($reason = null)
    {
        $this->update([
            'status' => self::STATUS_RETURNED,
            'notes' => $reason,
        ]);
        $this->clearCache();
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason,
        ]);
        $this->clearCache();
    }

    public function updateTrackingNumber($trackingNumber)
    {
        $this->update(['tracking_number' => $trackingNumber]);
        $this->clearCache();
    }

    public function updateEstimatedDeliveryDate($date)
    {
        $this->update(['estimated_delivery_date' => $date]);
        $this->clearCache();
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->tracking_number = null;
        $duplicate->shipped_at = null;
        $duplicate->actual_delivery_date = null;
        $duplicate->status = self::STATUS_PENDING;
        $duplicate->save();
        return $duplicate;
    }

    // Static Methods
    public static function getShipmentsCountForOrder($orderId)
    {
        return Cache::remember("order_shipments_count_{$orderId}", 3600, function () use ($orderId) {
            return static::where('order_id', $orderId)->count();
        });
    }

    public static function getPendingShipmentsCount()
    {
        return Cache::remember('pending_shipments_count', 3600, function () {
            return static::where('status', self::STATUS_PENDING)->count();
        });
    }

    public static function getDeliveredShipmentsCount()
    {
        return Cache::remember('delivered_shipments_count', 3600, function () {
            return static::where('status', self::STATUS_DELIVERED)->count();
        });
    }

    public static function getOverdueShipmentsCount()
    {
        return Cache::remember('overdue_shipments_count', 3600, function () {
            return static::overdue()->count();
        });
    }

    public static function getShipmentsForOrder($orderId)
    {
        return static::where('order_id', $orderId)->orderBy('created_at', 'desc')->get();
    }

    public static function getPendingShipments()
    {
        return static::where('status', self::STATUS_PENDING)->orderBy('created_at', 'asc')->get();
    }

    public static function getOverdueShipments()
    {
        return static::overdue()->orderBy('estimated_delivery_date', 'asc')->get();
    }

    public static function getTodayShipments()
    {
        return static::today()->orderBy('created_at', 'desc')->get();
    }

    public static function getThisWeekShipments()
    {
        return static::thisWeek()->orderBy('created_at', 'desc')->get();
    }

    public static function getShipmentsByStatus($status)
    {
        return static::where('status', $status)->orderBy('created_at', 'desc')->get();
    }

    public static function getShipmentsByCarrier($carrier)
    {
        return static::where('carrier', $carrier)->orderBy('created_at', 'desc')->get();
    }

    public static function getShipmentsStats($orderId = null)
    {
        $query = static::query();
        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return Cache::remember("shipments_stats" . ($orderId ? "_{$orderId}" : ""), 3600, function () use ($query) {
            return [
                'total' => $query->count(),
                'pending' => $query->where('status', self::STATUS_PENDING)->count(),
                'processing' => $query->where('status', self::STATUS_PROCESSING)->count(),
                'shipped' => $query->where('status', self::STATUS_SHIPPED)->count(),
                'in_transit' => $query->where('status', self::STATUS_IN_TRANSIT)->count(),
                'out_for_delivery' => $query->where('status', self::STATUS_OUT_FOR_DELIVERY)->count(),
                'delivered' => $query->where('status', self::STATUS_DELIVERED)->count(),
                'failed' => $query->where('status', self::STATUS_FAILED)->count(),
                'returned' => $query->where('status', self::STATUS_RETURNED)->count(),
                'cancelled' => $query->where('status', self::STATUS_CANCELLED)->count(),
                'overdue' => $query->overdue()->count(),
                'on_time_deliveries' => $query->where('status', self::STATUS_DELIVERED)
                    ->where('actual_delivery_date', '<=', 'estimated_delivery_date')->count(),
                'late_deliveries' => $query->where('status', self::STATUS_DELIVERED)
                    ->where('actual_delivery_date', '>', 'estimated_delivery_date')->count(),
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        $orderId = $this->order_id;
        Cache::forget("order_shipments_count_{$orderId}");
        Cache::forget("shipments_stats_{$orderId}");
        Cache::forget('pending_shipments_count');
        Cache::forget('delivered_shipments_count');
        Cache::forget('overdue_shipments_count');
        Cache::forget('shipments_stats');
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($shipment) {
            if (!$shipment->status) {
                $shipment->status = self::STATUS_PENDING;
            }
            if (!$shipment->service_type) {
                $shipment->service_type = self::SERVICE_STANDARD;
            }
            if (!$shipment->weight_unit) {
                $shipment->weight_unit = 'kg';
            }
        });

        static::created(function ($shipment) {
            $shipment->clearCache();
        });

        static::updated(function ($shipment) {
            $shipment->clearCache();
        });

        static::deleted(function ($shipment) {
            $shipment->clearCache();
        });
    }
}