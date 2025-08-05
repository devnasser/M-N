<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'favorable_type',
        'favorable_id',
        'notes',
        'priority',
        'is_public',
        'metadata',
    ];

    protected $hidden = [
        'metadata',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function favorable()
    {
        return $this->morphTo();
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Scopes
    public function scopeByUser(Builder $query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByFavorableType(Builder $query, $type)
    {
        return $query->where('favorable_type', $type);
    }

    public function scopeByFavorableId(Builder $query, $id)
    {
        return $query->where('favorable_id', $id);
    }

    public function scopePublic(Builder $query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate(Builder $query)
    {
        return $query->where('is_public', false);
    }

    public function scopeByPriority(Builder $query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority(Builder $query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeOldest(Builder $query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeNewest(Builder $query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByPriorityOrder(Builder $query)
    {
        return $query->orderByRaw("
            CASE 
                WHEN priority = 'high' THEN 1
                WHEN priority = 'medium' THEN 2
                WHEN priority = 'low' THEN 3
                ELSE 4
            END
        ")->orderBy('created_at', 'desc');
    }

    // Helper Methods
    public function getFavorableNameAttribute()
    {
        if (!$this->favorable) {
            return 'Unknown Item';
        }

        if (method_exists($this->favorable, 'getNameAttribute')) {
            return $this->favorable->name;
        }

        if (method_exists($this->favorable, 'getTitleAttribute')) {
            return $this->favorable->title;
        }

        return class_basename($this->favorable_type);
    }

    public function getFavorableNameEnAttribute()
    {
        if (!$this->favorable) {
            return 'Unknown Item';
        }

        if (method_exists($this->favorable, 'getNameEnAttribute')) {
            return $this->favorable->name_en;
        }

        if (method_exists($this->favorable, 'getTitleEnAttribute')) {
            return $this->favorable->title_en;
        }

        return $this->getFavorableNameAttribute();
    }

    public function getFavorableImageAttribute()
    {
        if (!$this->favorable) {
            return null;
        }

        if (method_exists($this->favorable, 'getImageAttribute')) {
            return $this->favorable->image;
        }

        if (method_exists($this->favorable, 'getLogoAttribute')) {
            return $this->favorable->logo;
        }

        return null;
    }

    public function getFavorableImageUrlAttribute()
    {
        $image = $this->getFavorableImageAttribute();
        
        if (!$image) {
            return null;
        }

        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        return asset('storage/' . $image);
    }

    public function getFavorableUrlAttribute()
    {
        if (!$this->favorable) {
            return null;
        }

        $favorableType = class_basename($this->favorable_type);
        
        switch ($favorableType) {
            case 'Product':
                return route('products.show', $this->favorable_id);
            case 'Brand':
                return route('brands.show', $this->favorable_id);
            case 'Category':
                return route('categories.show', $this->favorable_id);
            case 'Technician':
                return route('technicians.show', $this->favorable_id);
            default:
                return null;
        }
    }

    public function getFavorableTypeNameAttribute()
    {
        $favorableType = class_basename($this->favorable_type);
        
        switch ($favorableType) {
            case 'Product':
                return 'Product';
            case 'Brand':
                return 'Brand';
            case 'Category':
                return 'Category';
            case 'Technician':
                return 'Technician';
            default:
                return $favorableType;
        }
    }

    public function getFavorableTypeNameArAttribute()
    {
        $favorableType = class_basename($this->favorable_type);
        
        switch ($favorableType) {
            case 'Product':
                return 'المنتج';
            case 'Brand':
                return 'العلامة التجارية';
            case 'Category':
                return 'الفئة';
            case 'Technician':
                return 'الفني';
            default:
                return $favorableType;
        }
    }

    public function getFavorableTypeIconAttribute()
    {
        $favorableType = class_basename($this->favorable_type);
        
        switch ($favorableType) {
            case 'Product':
                return 'fas fa-box';
            case 'Brand':
                return 'fas fa-tag';
            case 'Category':
                return 'fas fa-folder';
            case 'Technician':
                return 'fas fa-user-cog';
            default:
                return 'fas fa-heart';
        }
    }

    public function getPriorityNameAttribute()
    {
        switch ($this->priority) {
            case 'high':
                return 'High';
            case 'medium':
                return 'Medium';
            case 'low':
                return 'Low';
            default:
                return 'Normal';
        }
    }

    public function getPriorityNameArAttribute()
    {
        switch ($this->priority) {
            case 'high':
                return 'عالية';
            case 'medium':
                return 'متوسطة';
            case 'low':
                return 'منخفضة';
            default:
                return 'عادية';
        }
    }

    public function getPriorityBadgeAttribute()
    {
        switch ($this->priority) {
            case 'high':
                return '<span class="badge bg-danger">High Priority</span>';
            case 'medium':
                return '<span class="badge bg-warning">Medium Priority</span>';
            case 'low':
                return '<span class="badge bg-info">Low Priority</span>';
            default:
                return '<span class="badge bg-secondary">Normal</span>';
        }
    }

    public function getPriorityBadgeArAttribute()
    {
        switch ($this->priority) {
            case 'high':
                return '<span class="badge bg-danger">أولوية عالية</span>';
            case 'medium':
                return '<span class="badge bg-warning">أولوية متوسطة</span>';
            case 'low':
                return '<span class="badge bg-info">أولوية منخفضة</span>';
            default:
                return '<span class="badge bg-secondary">عادية</span>';
        }
    }

    public function getPublicBadgeAttribute()
    {
        return $this->is_public 
            ? '<span class="badge bg-success">Public</span>'
            : '<span class="badge bg-secondary">Private</span>';
    }

    public function getPublicBadgeArAttribute()
    {
        return $this->is_public 
            ? '<span class="badge bg-success">عام</span>'
            : '<span class="badge bg-secondary">خاص</span>';
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

    public function getNotesPreviewAttribute()
    {
        if (!$this->notes) {
            return null;
        }

        return strlen($this->notes) > 100 
            ? substr($this->notes, 0, 100) . '...'
            : $this->notes;
    }

    public function getNotesPreviewArAttribute()
    {
        return $this->getNotesPreviewAttribute();
    }

    // Business Logic
    public function isPublic()
    {
        return $this->is_public;
    }

    public function isPrivate()
    {
        return !$this->is_public;
    }

    public function isHighPriority()
    {
        return $this->priority === 'high';
    }

    public function isMediumPriority()
    {
        return $this->priority === 'medium';
    }

    public function isLowPriority()
    {
        return $this->priority === 'low';
    }

    public function hasNotes()
    {
        return !empty($this->notes);
    }

    public function canBeEdited()
    {
        return true;
    }

    public function canBeDeleted()
    {
        return true;
    }

    public function makePublic()
    {
        $this->update(['is_public' => true]);
        $this->clearCache();
    }

    public function makePrivate()
    {
        $this->update(['is_public' => false]);
        $this->clearCache();
    }

    public function setPriority($priority)
    {
        $validPriorities = ['high', 'medium', 'low', 'normal'];
        
        if (in_array($priority, $validPriorities)) {
            $this->update(['priority' => $priority]);
            $this->clearCache();
        }
    }

    public function addNotes($notes)
    {
        $this->update(['notes' => $notes]);
        $this->clearCache();
    }

    public function clearNotes()
    {
        $this->update(['notes' => null]);
        $this->clearCache();
    }

    public function duplicate()
    {
        return $this->replicate()->save();
    }

    public function getFavorablePrice()
    {
        if (!$this->favorable) {
            return null;
        }

        if (method_exists($this->favorable, 'getCurrentPrice')) {
            return $this->favorable->getCurrentPrice();
        }

        if (method_exists($this->favorable, 'getPriceAttribute')) {
            return $this->favorable->price;
        }

        return null;
    }

    public function getFavorablePriceFormatted()
    {
        $price = $this->getFavorablePrice();
        
        if ($price === null) {
            return null;
        }

        return number_format($price, 2) . ' SAR';
    }

    public function getFavorablePriceFormattedAr()
    {
        $price = $this->getFavorablePrice();
        
        if ($price === null) {
            return null;
        }

        return number_format($price, 2) . ' ريال';
    }

    public function isFavorableAvailable()
    {
        if (!$this->favorable) {
            return false;
        }

        if (method_exists($this->favorable, 'isInStock')) {
            return $this->favorable->isInStock();
        }

        if (method_exists($this->favorable, 'isActive')) {
            return $this->favorable->isActive();
        }

        return true;
    }

    public function getFavorableAvailabilityBadge()
    {
        return $this->isFavorableAvailable()
            ? '<span class="badge bg-success">Available</span>'
            : '<span class="badge bg-danger">Unavailable</span>';
    }

    public function getFavorableAvailabilityBadgeAr()
    {
        return $this->isFavorableAvailable()
            ? '<span class="badge bg-success">متوفر</span>'
            : '<span class="badge bg-danger">غير متوفر</span>';
    }

    // Static Methods
    public static function getFavoritesCountForUser($userId)
    {
        return Cache::remember("user_favorites_count_{$userId}", 3600, function () use ($userId) {
            return static::where('user_id', $userId)->count();
        });
    }

    public static function getFavoritesCountByType($userId, $type)
    {
        return Cache::remember("user_favorites_count_{$userId}_{$type}", 3600, function () use ($userId, $type) {
            return static::where('user_id', $userId)
                ->where('favorable_type', $type)
                ->count();
        });
    }

    public static function getPublicFavoritesCount()
    {
        return Cache::remember('public_favorites_count', 3600, function () {
            return static::where('is_public', true)->count();
        });
    }

    public static function getMostFavoritedItems($type = null, $limit = 10)
    {
        $query = static::selectRaw('favorable_type, favorable_id, COUNT(*) as favorite_count')
            ->groupBy('favorable_type', 'favorable_id')
            ->orderBy('favorite_count', 'desc')
            ->limit($limit);

        if ($type) {
            $query->where('favorable_type', $type);
        }

        return $query->get();
    }

    public static function getRecentFavorites($limit = 10)
    {
        return static::with(['user', 'favorable'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getHighPriorityFavorites($userId)
    {
        return static::where('user_id', $userId)
            ->where('priority', 'high')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getFavoritesByPriority($userId, $priority)
    {
        return static::where('user_id', $userId)
            ->where('priority', $priority)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function searchFavorites($userId, $search)
    {
        return static::where('user_id', $userId)
            ->where(function ($query) use ($search) {
                $query->where('notes', 'like', "%{$search}%")
                    ->orWhereHasMorph('favorable', [
                        Product::class,
                        Brand::class,
                        Category::class,
                        Technician::class
                    ], function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('description_en', 'like', "%{$search}%");
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getFavoritesStats($userId)
    {
        return Cache::remember("user_favorites_stats_{$userId}", 3600, function () use ($userId) {
            $favorites = static::where('user_id', $userId);
            
            return [
                'total' => $favorites->count(),
                'public' => $favorites->where('is_public', true)->count(),
                'private' => $favorites->where('is_public', false)->count(),
                'high_priority' => $favorites->where('priority', 'high')->count(),
                'medium_priority' => $favorites->where('priority', 'medium')->count(),
                'low_priority' => $favorites->where('priority', 'low')->count(),
                'with_notes' => $favorites->whereNotNull('notes')->count(),
                'recent' => $favorites->where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        $userId = $this->user_id;
        
        Cache::forget("user_favorites_count_{$userId}");
        Cache::forget("user_favorites_stats_{$userId}");
        
        $favorableType = class_basename($this->favorable_type);
        Cache::forget("user_favorites_count_{$userId}_{$favorableType}");
        
        Cache::forget('public_favorites_count');
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($favorite) {
            if (!$favorite->priority) {
                $favorite->priority = 'normal';
            }
            
            if (!isset($favorite->is_public)) {
                $favorite->is_public = false;
            }
        });

        static::created(function ($favorite) {
            $favorite->clearCache();
        });

        static::updated(function ($favorite) {
            $favorite->clearCache();
        });

        static::deleted(function ($favorite) {
            $favorite->clearCache();
        });
    }
} 