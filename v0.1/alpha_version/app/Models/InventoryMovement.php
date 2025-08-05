<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'reference_number',
        'notes',
        'notes_en',
        'location',
        'location_en',
        'reason',
        'reason_en',
        'performed_by',
        'approved_by',
        'approved_at',
        'status',
        'metadata',
    ];

    protected $hidden = [
        'unit_cost',
        'total_cost',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'approved_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Movement Types
    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_RETURN = 'return';
    const TYPE_DAMAGED = 'damaged';
    const TYPE_EXPIRED = 'expired';
    const TYPE_LOST = 'lost';
    const TYPE_FOUND = 'found';

    // Reference Types
    const REF_ORDER = 'order';
    const REF_PURCHASE = 'purchase';
    const REF_RETURN = 'return';
    const REF_ADJUSTMENT = 'adjustment';
    const REF_TRANSFER = 'transfer';
    const REF_MANUAL = 'manual';
    const REF_SYSTEM = 'system';

    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Scopes
    public function scopeByProduct(Builder $query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByProductVariant(Builder $query, $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }

    public function scopeByMovementType(Builder $query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeIn(Builder $query)
    {
        return $query->where('movement_type', self::TYPE_IN);
    }

    public function scopeOut(Builder $query)
    {
        return $query->where('movement_type', self::TYPE_OUT);
    }

    public function scopeAdjustment(Builder $query)
    {
        return $query->where('movement_type', self::TYPE_ADJUSTMENT);
    }

    public function scopeTransfer(Builder $query)
    {
        return $query->where('movement_type', self::TYPE_TRANSFER);
    }

    public function scopeReturn(Builder $query)
    {
        return $query->where('movement_type', self::TYPE_RETURN);
    }

    public function scopeDamaged(Builder $query)
    {
        return $query->where('movement_type', self::TYPE_DAMAGED);
    }

    public function scopeExpired(Builder $query)
    {
        return $query->where('movement_type', self::TYPE_EXPIRED);
    }

    public function scopeLost(Builder $query)
    {
        return $query->where('movement_type', self::TYPE_LOST);
    }

    public function scopeFound(Builder $query)
    {
        return $query->where('movement_type', self::TYPE_FOUND);
    }

    public function scopeByReferenceType(Builder $query, $type)
    {
        return $query->where('reference_type', $type);
    }

    public function scopeByReferenceId(Builder $query, $id)
    {
        return $query->where('reference_id', $id);
    }

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected(Builder $query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeCancelled(Builder $query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeByPerformer(Builder $query, $userId)
    {
        return $query->where('performed_by', $userId);
    }

    public function scopeByApprover(Builder $query, $userId)
    {
        return $query->where('approved_by', $userId);
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
        return $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
    }

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeByQuantityRange(Builder $query, $minQuantity = null, $maxQuantity = null)
    {
        if ($minQuantity) {
            $query->where('quantity', '>=', $minQuantity);
        }
        
        if ($maxQuantity) {
            $query->where('quantity', '<=', $maxQuantity);
        }
        
        return $query;
    }

    public function scopeByCostRange(Builder $query, $minCost = null, $maxCost = null)
    {
        if ($minCost) {
            $query->where('total_cost', '>=', $minCost);
        }
        
        if ($maxCost) {
            $query->where('total_cost', '<=', $maxCost);
        }
        
        return $query;
    }

    public function scopeLargeMovements(Builder $query, $threshold = 100)
    {
        return $query->where('quantity', '>', $threshold);
    }

    public function scopeSmallMovements(Builder $query, $threshold = 10)
    {
        return $query->where('quantity', '<', $threshold);
    }

    public function scopeExpensiveMovements(Builder $query, $threshold = 1000)
    {
        return $query->where('total_cost', '>', $threshold);
    }

    public function scopeCheapMovements(Builder $query, $threshold = 100)
    {
        return $query->where('total_cost', '<', $threshold);
    }

    // Helper Methods
    public function getNotesAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->notes) {
            return $this->notes;
        }
        
        return $value ?: $this->notes_en;
    }

    public function getLocationAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->location) {
            return $this->location;
        }
        
        return $value ?: $this->location_en;
    }

    public function getReasonAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->reason) {
            return $this->reason;
        }
        
        return $value ?: $this->reason_en;
    }

    public function getNotesArAttribute()
    {
        return $this->notes;
    }

    public function getLocationArAttribute()
    {
        return $this->location;
    }

    public function getReasonArAttribute()
    {
        return $this->reason;
    }

    public function getNotesEnAttribute()
    {
        return $this->notes_en;
    }

    public function getLocationEnAttribute()
    {
        return $this->location_en;
    }

    public function getReasonEnAttribute()
    {
        return $this->reason_en;
    }

    public function getMovementTypeNameAttribute()
    {
        switch ($this->movement_type) {
            case self::TYPE_IN:
                return 'Stock In';
            case self::TYPE_OUT:
                return 'Stock Out';
            case self::TYPE_ADJUSTMENT:
                return 'Adjustment';
            case self::TYPE_TRANSFER:
                return 'Transfer';
            case self::TYPE_RETURN:
                return 'Return';
            case self::TYPE_DAMAGED:
                return 'Damaged';
            case self::TYPE_EXPIRED:
                return 'Expired';
            case self::TYPE_LOST:
                return 'Lost';
            case self::TYPE_FOUND:
                return 'Found';
            default:
                return ucfirst($this->movement_type);
        }
    }

    public function getMovementTypeNameArAttribute()
    {
        switch ($this->movement_type) {
            case self::TYPE_IN:
                return 'وارد مخزون';
            case self::TYPE_OUT:
                return 'صادر مخزون';
            case self::TYPE_ADJUSTMENT:
                return 'تعديل';
            case self::TYPE_TRANSFER:
                return 'نقل';
            case self::TYPE_RETURN:
                return 'إرجاع';
            case self::TYPE_DAMAGED:
                return 'تالف';
            case self::TYPE_EXPIRED:
                return 'منتهي الصلاحية';
            case self::TYPE_LOST:
                return 'مفقود';
            case self::TYPE_FOUND:
                return 'موجود';
            default:
                return ucfirst($this->movement_type);
        }
    }

    public function getMovementTypeIconAttribute()
    {
        switch ($this->movement_type) {
            case self::TYPE_IN:
                return 'fas fa-arrow-down text-success';
            case self::TYPE_OUT:
                return 'fas fa-arrow-up text-danger';
            case self::TYPE_ADJUSTMENT:
                return 'fas fa-edit text-warning';
            case self::TYPE_TRANSFER:
                return 'fas fa-exchange-alt text-info';
            case self::TYPE_RETURN:
                return 'fas fa-undo text-primary';
            case self::TYPE_DAMAGED:
                return 'fas fa-times-circle text-danger';
            case self::TYPE_EXPIRED:
                return 'fas fa-calendar-times text-warning';
            case self::TYPE_LOST:
                return 'fas fa-search text-secondary';
            case self::TYPE_FOUND:
                return 'fas fa-check-circle text-success';
            default:
                return 'fas fa-box text-secondary';
        }
    }

    public function getReferenceTypeNameAttribute()
    {
        switch ($this->reference_type) {
            case self::REF_ORDER:
                return 'Order';
            case self::REF_PURCHASE:
                return 'Purchase';
            case self::REF_RETURN:
                return 'Return';
            case self::REF_ADJUSTMENT:
                return 'Adjustment';
            case self::REF_TRANSFER:
                return 'Transfer';
            case self::REF_MANUAL:
                return 'Manual';
            case self::REF_SYSTEM:
                return 'System';
            default:
                return ucfirst($this->reference_type);
        }
    }

    public function getReferenceTypeNameArAttribute()
    {
        switch ($this->reference_type) {
            case self::REF_ORDER:
                return 'طلب';
            case self::REF_PURCHASE:
                return 'شراء';
            case self::REF_RETURN:
                return 'إرجاع';
            case self::REF_ADJUSTMENT:
                return 'تعديل';
            case self::REF_TRANSFER:
                return 'نقل';
            case self::REF_MANUAL:
                return 'يدوي';
            case self::REF_SYSTEM:
                return 'نظام';
            default:
                return ucfirst($this->reference_type);
        }
    }

    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'Pending';
            case self::STATUS_APPROVED:
                return 'Approved';
            case self::STATUS_REJECTED:
                return 'Rejected';
            case self::STATUS_CANCELLED:
                return 'Cancelled';
            default:
                return ucfirst($this->status);
        }
    }

    public function getStatusNameArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'في الانتظار';
            case self::STATUS_APPROVED:
                return 'موافق عليه';
            case self::STATUS_REJECTED:
                return 'مرفوض';
            case self::STATUS_CANCELLED:
                return 'ملغي';
            default:
                return ucfirst($this->status);
        }
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return '<span class="badge bg-warning">Pending</span>';
            case self::STATUS_APPROVED:
                return '<span class="badge bg-success">Approved</span>';
            case self::STATUS_REJECTED:
                return '<span class="badge bg-danger">Rejected</span>';
            case self::STATUS_CANCELLED:
                return '<span class="badge bg-secondary">Cancelled</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    public function getStatusBadgeArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return '<span class="badge bg-warning">في الانتظار</span>';
            case self::STATUS_APPROVED:
                return '<span class="badge bg-success">موافق عليه</span>';
            case self::STATUS_REJECTED:
                return '<span class="badge bg-danger">مرفوض</span>';
            case self::STATUS_CANCELLED:
                return '<span class="badge bg-secondary">ملغي</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getMovementTypeBadgeAttribute()
    {
        switch ($this->movement_type) {
            case self::TYPE_IN:
                return '<span class="badge bg-success">Stock In</span>';
            case self::TYPE_OUT:
                return '<span class="badge bg-danger">Stock Out</span>';
            case self::TYPE_ADJUSTMENT:
                return '<span class="badge bg-warning">Adjustment</span>';
            case self::TYPE_TRANSFER:
                return '<span class="badge bg-info">Transfer</span>';
            case self::TYPE_RETURN:
                return '<span class="badge bg-primary">Return</span>';
            case self::TYPE_DAMAGED:
                return '<span class="badge bg-danger">Damaged</span>';
            case self::TYPE_EXPIRED:
                return '<span class="badge bg-warning">Expired</span>';
            case self::TYPE_LOST:
                return '<span class="badge bg-secondary">Lost</span>';
            case self::TYPE_FOUND:
                return '<span class="badge bg-success">Found</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    public function getMovementTypeBadgeArAttribute()
    {
        switch ($this->movement_type) {
            case self::TYPE_IN:
                return '<span class="badge bg-success">وارد مخزون</span>';
            case self::TYPE_OUT:
                return '<span class="badge bg-danger">صادر مخزون</span>';
            case self::TYPE_ADJUSTMENT:
                return '<span class="badge bg-warning">تعديل</span>';
            case self::TYPE_TRANSFER:
                return '<span class="badge bg-info">نقل</span>';
            case self::TYPE_RETURN:
                return '<span class="badge bg-primary">إرجاع</span>';
            case self::TYPE_DAMAGED:
                return '<span class="badge bg-danger">تالف</span>';
            case self::TYPE_EXPIRED:
                return '<span class="badge bg-warning">منتهي الصلاحية</span>';
            case self::TYPE_LOST:
                return '<span class="badge bg-secondary">مفقود</span>';
            case self::TYPE_FOUND:
                return '<span class="badge bg-success">موجود</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getQuantityFormattedAttribute()
    {
        return number_format($this->quantity);
    }

    public function getQuantityFormattedArAttribute()
    {
        return $this->getQuantityFormattedAttribute();
    }

    public function getUnitCostFormattedAttribute()
    {
        return $this->unit_cost ? number_format($this->unit_cost, 2) . ' SAR' : null;
    }

    public function getUnitCostFormattedArAttribute()
    {
        return $this->unit_cost ? number_format($this->unit_cost, 2) . ' ريال' : null;
    }

    public function getTotalCostFormattedAttribute()
    {
        return $this->total_cost ? number_format($this->total_cost, 2) . ' SAR' : null;
    }

    public function getTotalCostFormattedArAttribute()
    {
        return $this->total_cost ? number_format($this->total_cost, 2) . ' ريال' : null;
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

    public function getApprovedAtFormattedAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('M d, Y H:i') : null;
    }

    public function getApprovedAtFormattedArAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('d M Y H:i') : null;
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

    public function getApprovalTimeAttribute()
    {
        if (!$this->approved_at) {
            return null;
        }
        
        return $this->created_at->diffForHumans($this->approved_at, true);
    }

    public function getApprovalTimeArAttribute()
    {
        if (!$this->approved_at) {
            return null;
        }
        
        $diff = $this->created_at->diff($this->approved_at);
        
        if ($diff->days > 0) {
            return $diff->days . ' يوم';
        } elseif ($diff->h > 0) {
            return $diff->h . ' ساعة';
        } elseif ($diff->i > 0) {
            return $diff->i . ' دقيقة';
        } else {
            return 'فوراً';
        }
    }

    // Business Logic
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isStockIn()
    {
        return $this->movement_type === self::TYPE_IN;
    }

    public function isStockOut()
    {
        return $this->movement_type === self::TYPE_OUT;
    }

    public function isAdjustment()
    {
        return $this->movement_type === self::TYPE_ADJUSTMENT;
    }

    public function isTransfer()
    {
        return $this->movement_type === self::TYPE_TRANSFER;
    }

    public function isReturn()
    {
        return $this->movement_type === self::TYPE_RETURN;
    }

    public function isDamaged()
    {
        return $this->movement_type === self::TYPE_DAMAGED;
    }

    public function isExpired()
    {
        return $this->movement_type === self::TYPE_EXPIRED;
    }

    public function isLost()
    {
        return $this->movement_type === self::TYPE_LOST;
    }

    public function isFound()
    {
        return $this->movement_type === self::TYPE_FOUND;
    }

    public function canBeApproved()
    {
        return $this->isPending();
    }

    public function canBeRejected()
    {
        return $this->isPending();
    }

    public function canBeCancelled()
    {
        return $this->isPending();
    }

    public function canBeEdited()
    {
        return $this->isPending();
    }

    public function canBeDeleted()
    {
        return $this->isPending() || $this->isCancelled();
    }

    public function approve($approverId = null)
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId ?: auth()->id(),
            'approved_at' => Carbon::now(),
        ]);

        // Apply the movement to inventory
        $this->applyToInventory();
        
        $this->clearCache();
        return true;
    }

    public function reject($approverId = null, $reason = null)
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approverId ?: auth()->id(),
            'approved_at' => Carbon::now(),
            'notes' => $reason ? ($this->notes . "\nRejection Reason: " . $reason) : $this->notes,
        ]);

        $this->clearCache();
        return true;
    }

    public function cancel($reason = null)
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason ? ($this->notes . "\nCancellation Reason: " . $reason) : $this->notes,
        ]);

        $this->clearCache();
        return true;
    }

    protected function applyToInventory()
    {
        $target = $this->productVariant ?: $this->product;
        
        if (!$target) {
            return false;
        }

        switch ($this->movement_type) {
            case self::TYPE_IN:
            case self::TYPE_RETURN:
            case self::TYPE_FOUND:
                $target->addStock($this->quantity);
                break;
                
            case self::TYPE_OUT:
            case self::TYPE_DAMAGED:
            case self::TYPE_EXPIRED:
            case self::TYPE_LOST:
                $target->deductStock($this->quantity);
                break;
                
            case self::TYPE_ADJUSTMENT:
                $target->updateStock($this->quantity);
                break;
                
            case self::TYPE_TRANSFER:
                // Transfer logic would be handled separately
                break;
        }
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->status = self::STATUS_PENDING;
        $duplicate->approved_by = null;
        $duplicate->approved_at = null;
        $duplicate->save();
        
        return $duplicate;
    }

    public function getReferenceUrl()
    {
        if (!$this->reference) {
            return null;
        }

        $referenceType = class_basename($this->reference_type);
        
        switch ($referenceType) {
            case 'Order':
                return route('admin.orders.show', $this->reference_id);
            case 'Product':
                return route('admin.products.show', $this->reference_id);
            case 'ProductVariant':
                return route('admin.product-variants.show', $this->reference_id);
            default:
                return null;
        }
    }

    public function getReferenceName()
    {
        if (!$this->reference) {
            return $this->reference_number ?: 'Unknown Reference';
        }

        if (method_exists($this->reference, 'getNameAttribute')) {
            return $this->reference->name;
        }

        if (method_exists($this->reference, 'getOrderNumberAttribute')) {
            return $this->reference->order_number;
        }

        return $this->reference_number ?: 'Unknown Reference';
    }

    // Static Methods
    public static function getMovementsCountForProduct($productId)
    {
        return Cache::remember("product_movements_count_{$productId}", 3600, function () use ($productId) {
            return static::where('product_id', $productId)->count();
        });
    }

    public static function getMovementsCountForVariant($variantId)
    {
        return Cache::remember("variant_movements_count_{$variantId}", 3600, function () use ($variantId) {
            return static::where('product_variant_id', $variantId)->count();
        });
    }

    public static function getPendingMovementsCount()
    {
        return Cache::remember('pending_movements_count', 3600, function () {
            return static::where('status', self::STATUS_PENDING)->count();
        });
    }

    public static function getMovementsForProduct($productId, $limit = 50)
    {
        return static::where('product_id', $productId)
            ->with(['performer', 'approver', 'reference'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getMovementsForVariant($variantId, $limit = 50)
    {
        return static::where('product_variant_id', $variantId)
            ->with(['performer', 'approver', 'reference'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getPendingMovements()
    {
        return static::where('status', self::STATUS_PENDING)
            ->with(['product', 'productVariant', 'performer'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public static function getRecentMovements($days = 7)
    {
        return static::where('created_at', '>=', Carbon::now()->subDays($days))
            ->with(['product', 'productVariant', 'performer', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getMovementsByType($type, $limit = 50)
    {
        return static::where('movement_type', $type)
            ->with(['product', 'productVariant', 'performer', 'approver'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getMovementsStats($productId = null, $variantId = null)
    {
        $query = static::query();
        
        if ($productId) {
            $query->where('product_id', $productId);
        }
        
        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }

        return Cache::remember("movements_stats" . ($productId ? "_{$productId}" : "") . ($variantId ? "_{$variantId}" : ""), 3600, function () use ($query) {
            return [
                'total' => $query->count(),
                'pending' => $query->where('status', self::STATUS_PENDING)->count(),
                'approved' => $query->where('status', self::STATUS_APPROVED)->count(),
                'rejected' => $query->where('status', self::STATUS_REJECTED)->count(),
                'cancelled' => $query->where('status', self::STATUS_CANCELLED)->count(),
                'stock_in' => $query->where('movement_type', self::TYPE_IN)->count(),
                'stock_out' => $query->where('movement_type', self::TYPE_OUT)->count(),
                'adjustment' => $query->where('movement_type', self::TYPE_ADJUSTMENT)->count(),
                'transfer' => $query->where('movement_type', self::TYPE_TRANSFER)->count(),
                'return' => $query->where('movement_type', self::TYPE_RETURN)->count(),
                'damaged' => $query->where('movement_type', self::TYPE_DAMAGED)->count(),
                'expired' => $query->where('movement_type', self::TYPE_EXPIRED)->count(),
                'lost' => $query->where('movement_type', self::TYPE_LOST)->count(),
                'found' => $query->where('movement_type', self::TYPE_FOUND)->count(),
                'total_quantity_in' => $query->where('movement_type', self::TYPE_IN)->sum('quantity'),
                'total_quantity_out' => $query->where('movement_type', self::TYPE_OUT)->sum('quantity'),
                'total_cost' => $query->sum('total_cost'),
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        $productId = $this->product_id;
        $variantId = $this->product_variant_id;
        
        Cache::forget("product_movements_count_{$productId}");
        Cache::forget("variant_movements_count_{$variantId}");
        Cache::forget('pending_movements_count');
        Cache::forget("movements_stats_{$productId}");
        Cache::forget("movements_stats_{$variantId}");
        Cache::forget("movements_stats");
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($movement) {
            if (!$movement->status) {
                $movement->status = self::STATUS_PENDING;
            }
            
            if (!$movement->performed_by) {
                $movement->performed_by = auth()->id();
            }
            
            if (!$movement->total_cost && $movement->unit_cost && $movement->quantity) {
                $movement->total_cost = $movement->unit_cost * $movement->quantity;
            }
        });

        static::created(function ($movement) {
            $movement->clearCache();
        });

        static::updated(function ($movement) {
            $movement->clearCache();
        });

        static::deleted(function ($movement) {
            $movement->clearCache();
        });
    }
}