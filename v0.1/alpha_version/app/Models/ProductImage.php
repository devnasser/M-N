<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'alt_text_en',
        'caption',
        'caption_en',
        'sort_order',
        'is_primary',
        'is_active',
        'file_size',
        'file_type',
        'dimensions',
        'metadata',
    ];

    protected $hidden = [
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'dimensions' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'primary_image_id');
    }

    // Scopes
    public function scopeByProduct(Builder $query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary(Builder $query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeSecondary(Builder $query)
    {
        return $query->where('is_primary', false);
    }

    public function scopeBySortOrder(Builder $query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function scopeByFileType(Builder $query, $type)
    {
        return $query->where('file_type', $type);
    }

    public function scopeByFileSize(Builder $query, $minSize = null, $maxSize = null)
    {
        if ($minSize) {
            $query->where('file_size', '>=', $minSize);
        }
        
        if ($maxSize) {
            $query->where('file_size', '<=', $maxSize);
        }
        
        return $query;
    }

    public function scopeLargeFiles(Builder $query, $threshold = 1024 * 1024) // 1MB
    {
        return $query->where('file_size', '>', $threshold);
    }

    public function scopeSmallFiles(Builder $query, $threshold = 100 * 1024) // 100KB
    {
        return $query->where('file_size', '<', $threshold);
    }

    public function scopeWithAltText(Builder $query)
    {
        return $query->whereNotNull('alt_text');
    }

    public function scopeWithoutAltText(Builder $query)
    {
        return $query->whereNull('alt_text');
    }

    public function scopeWithCaption(Builder $query)
    {
        return $query->whereNotNull('caption');
    }

    public function scopeWithoutCaption(Builder $query)
    {
        return $query->whereNull('caption');
    }

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // Helper Methods
    public function getImageUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }

        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return $this->image_path;
        }

        return asset('storage/' . $this->image_path);
    }

    public function getThumbnailUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }

        $path = $this->image_path;
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $directory = pathinfo($path, PATHINFO_DIRNAME);
        
        $thumbnailPath = $directory . '/' . $filename . '_thumb.' . $extension;
        
        if (Storage::disk('public')->exists($thumbnailPath)) {
            return asset('storage/' . $thumbnailPath);
        }

        return $this->getImageUrlAttribute();
    }

    public function getMediumUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }

        $path = $this->image_path;
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $directory = pathinfo($path, PATHINFO_DIRNAME);
        
        $mediumPath = $directory . '/' . $filename . '_medium.' . $extension;
        
        if (Storage::disk('public')->exists($mediumPath)) {
            return asset('storage/' . $mediumPath);
        }

        return $this->getImageUrlAttribute();
    }

    public function getLargeUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }

        $path = $this->image_path;
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $directory = pathinfo($path, PATHINFO_DIRNAME);
        
        $largePath = $directory . '/' . $filename . '_large.' . $extension;
        
        if (Storage::disk('public')->exists($largePath)) {
            return asset('storage/' . $largePath);
        }

        return $this->getImageUrlAttribute();
    }

    public function getAltTextAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->alt_text) {
            return $this->alt_text;
        }
        
        return $value ?: $this->alt_text_en;
    }

    public function getCaptionAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->caption) {
            return $this->caption;
        }
        
        return $value ?: $this->caption_en;
    }

    public function getAltTextArAttribute()
    {
        return $this->alt_text;
    }

    public function getCaptionArAttribute()
    {
        return $this->caption;
    }

    public function getAltTextEnAttribute()
    {
        return $this->alt_text_en;
    }

    public function getCaptionEnAttribute()
    {
        return $this->caption_en;
    }

    public function getFileNameAttribute()
    {
        return $this->image_path ? basename($this->image_path) : null;
    }

    public function getFileExtensionAttribute()
    {
        return $this->image_path ? pathinfo($this->image_path, PATHINFO_EXTENSION) : null;
    }

    public function getFileSizeFormattedAttribute()
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getFileSizeFormattedArAttribute()
    {
        return $this->getFileSizeFormattedAttribute();
    }

    public function getFileTypeNameAttribute()
    {
        switch (strtolower($this->file_type)) {
            case 'jpg':
            case 'jpeg':
                return 'JPEG Image';
            case 'png':
                return 'PNG Image';
            case 'gif':
                return 'GIF Image';
            case 'webp':
                return 'WebP Image';
            case 'svg':
                return 'SVG Image';
            default:
                return strtoupper($this->file_type) . ' File';
        }
    }

    public function getFileTypeNameArAttribute()
    {
        switch (strtolower($this->file_type)) {
            case 'jpg':
            case 'jpeg':
                return 'صورة JPEG';
            case 'png':
                return 'صورة PNG';
            case 'gif':
                return 'صورة GIF';
            case 'webp':
                return 'صورة WebP';
            case 'svg':
                return 'صورة SVG';
            default:
                return 'ملف ' . strtoupper($this->file_type);
        }
    }

    public function getDimensionsFormattedAttribute()
    {
        if (!$this->dimensions || !isset($this->dimensions['width']) || !isset($this->dimensions['height'])) {
            return null;
        }

        return $this->dimensions['width'] . ' × ' . $this->dimensions['height'];
    }

    public function getDimensionsFormattedArAttribute()
    {
        return $this->getDimensionsFormattedAttribute();
    }

    public function getWidthAttribute()
    {
        return $this->dimensions['width'] ?? null;
    }

    public function getHeightAttribute()
    {
        return $this->dimensions['height'] ?? null;
    }

    public function getAspectRatioAttribute()
    {
        if (!$this->width || !$this->height) {
            return null;
        }

        return round($this->width / $this->height, 2);
    }

    public function getAspectRatioFormattedAttribute()
    {
        $ratio = $this->getAspectRatioAttribute();
        
        if (!$ratio) {
            return null;
        }

        return $ratio . ':1';
    }

    public function getAspectRatioFormattedArAttribute()
    {
        return $this->getAspectRatioFormattedAttribute();
    }

    public function getIsSquareAttribute()
    {
        $ratio = $this->getAspectRatioAttribute();
        return $ratio && abs($ratio - 1) < 0.1;
    }

    public function getIsPortraitAttribute()
    {
        $ratio = $this->getAspectRatioAttribute();
        return $ratio && $ratio < 1;
    }

    public function getIsLandscapeAttribute()
    {
        $ratio = $this->getAspectRatioAttribute();
        return $ratio && $ratio > 1;
    }

    public function getPrimaryBadgeAttribute()
    {
        return $this->is_primary 
            ? '<span class="badge bg-primary">Primary</span>'
            : '<span class="badge bg-secondary">Secondary</span>';
    }

    public function getPrimaryBadgeArAttribute()
    {
        return $this->is_primary 
            ? '<span class="badge bg-primary">رئيسية</span>'
            : '<span class="badge bg-secondary">ثانوية</span>';
    }

    public function getActiveBadgeAttribute()
    {
        return $this->is_active 
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-danger">Inactive</span>';
    }

    public function getActiveBadgeArAttribute()
    {
        return $this->is_active 
            ? '<span class="badge bg-success">نشطة</span>'
            : '<span class="badge bg-danger">غير نشطة</span>';
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

    // Business Logic
    public function isPrimary()
    {
        return $this->is_primary;
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function hasAltText()
    {
        return !empty($this->alt_text) || !empty($this->alt_text_en);
    }

    public function hasCaption()
    {
        return !empty($this->caption) || !empty($this->caption_en);
    }

    public function canBePrimary()
    {
        return $this->is_active;
    }

    public function canBeDeleted()
    {
        return !$this->is_primary || $this->product->images()->where('is_primary', true)->count() > 1;
    }

    public function makePrimary()
    {
        // Remove primary from other images of the same product
        $this->product->images()
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
        $this->clearCache();
    }

    public function makeSecondary()
    {
        $this->update(['is_primary' => false]);
        $this->clearCache();
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
        $this->clearCache();
    }

    public function deactivate()
    {
        if ($this->is_primary) {
            return false; // Cannot deactivate primary image
        }

        $this->update(['is_active' => false]);
        $this->clearCache();
        return true;
    }

    public function moveUp()
    {
        $previousImage = $this->product->images()
            ->where('sort_order', '<', $this->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previousImage) {
            $this->swapSortOrder($previousImage);
        }
    }

    public function moveDown()
    {
        $nextImage = $this->product->images()
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($nextImage) {
            $this->swapSortOrder($nextImage);
        }
    }

    public function moveToPosition($position)
    {
        $images = $this->product->images()->orderBy('sort_order')->get();
        $currentPosition = $images->search(function ($image) {
            return $image->id === $this->id;
        });

        if ($currentPosition === false || $position < 0 || $position >= $images->count()) {
            return false;
        }

        if ($currentPosition < $position) {
            // Moving down
            for ($i = $currentPosition; $i < $position; $i++) {
                $this->swapSortOrder($images[$i + 1]);
            }
        } else {
            // Moving up
            for ($i = $currentPosition; $i > $position; $i--) {
                $this->swapSortOrder($images[$i - 1]);
            }
        }

        return true;
    }

    protected function swapSortOrder($otherImage)
    {
        $tempOrder = $this->sort_order;
        $this->update(['sort_order' => $otherImage->sort_order]);
        $otherImage->update(['sort_order' => $tempOrder]);
        
        $this->clearCache();
        $otherImage->clearCache();
    }

    public function updateAltText($altText, $locale = null)
    {
        if ($locale === 'ar' || app()->getLocale() === 'ar') {
            $this->update(['alt_text' => $altText]);
        } else {
            $this->update(['alt_text_en' => $altText]);
        }
        
        $this->clearCache();
    }

    public function updateCaption($caption, $locale = null)
    {
        if ($locale === 'ar' || app()->getLocale() === 'ar') {
            $this->update(['caption' => $caption]);
        } else {
            $this->update(['caption_en' => $caption]);
        }
        
        $this->clearCache();
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->is_primary = false;
        $duplicate->sort_order = $this->product->images()->max('sort_order') + 1;
        $duplicate->save();
        
        return $duplicate;
    }

    public function optimize()
    {
        if (!$this->image_path || !Storage::disk('public')->exists($this->image_path)) {
            return false;
        }

        try {
            $image = Image::make(Storage::disk('public')->path($this->image_path));
            
            // Optimize quality
            $image->save(Storage::disk('public')->path($this->image_path), 85);
            
            // Update file size
            $this->update(['file_size' => Storage::disk('public')->size($this->image_path)]);
            
            $this->clearCache();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function generateThumbnails()
    {
        if (!$this->image_path || !Storage::disk('public')->exists($this->image_path)) {
            return false;
        }

        try {
            $image = Image::make(Storage::disk('public')->path($this->image_path));
            $path = $this->image_path;
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $filename = pathinfo($path, PATHINFO_FILENAME);
            $directory = pathinfo($path, PATHINFO_DIRNAME);

            // Generate thumbnail (150x150)
            $thumbnail = $image->fit(150, 150);
            $thumbnailPath = $directory . '/' . $filename . '_thumb.' . $extension;
            $thumbnail->save(Storage::disk('public')->path($thumbnailPath));

            // Generate medium (400x400)
            $medium = $image->fit(400, 400);
            $mediumPath = $directory . '/' . $filename . '_medium.' . $extension;
            $medium->save(Storage::disk('public')->path($mediumPath));

            // Generate large (800x800)
            $large = $image->fit(800, 800);
            $largePath = $directory . '/' . $filename . '_large.' . $extension;
            $large->save(Storage::disk('public')->path($largePath));

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Static Methods
    public static function getImagesCountForProduct($productId)
    {
        return Cache::remember("product_images_count_{$productId}", 3600, function () use ($productId) {
            return static::where('product_id', $productId)->count();
        });
    }

    public static function getActiveImagesCountForProduct($productId)
    {
        return Cache::remember("product_active_images_count_{$productId}", 3600, function () use ($productId) {
            return static::where('product_id', $productId)
                ->where('is_active', true)
                ->count();
        });
    }

    public static function getPrimaryImageForProduct($productId)
    {
        return Cache::remember("product_primary_image_{$productId}", 3600, function () use ($productId) {
            return static::where('product_id', $productId)
                ->where('is_primary', true)
                ->where('is_active', true)
                ->first();
        });
    }

    public static function getImagesForProduct($productId, $activeOnly = true)
    {
        $query = static::where('product_id', $productId);
        
        if ($activeOnly) {
            $query->where('is_active', true);
        }
        
        return $query->orderBy('sort_order')->get();
    }

    public static function getLargeImages($threshold = 1024 * 1024) // 1MB
    {
        return static::where('file_size', '>', $threshold)->get();
    }

    public static function getImagesWithoutAltText()
    {
        return static::whereNull('alt_text')
            ->whereNull('alt_text_en')
            ->get();
    }

    public static function getImagesWithoutCaption()
    {
        return static::whereNull('caption')
            ->whereNull('caption_en')
            ->get();
    }

    public static function getImagesStats($productId = null)
    {
        $query = static::query();
        
        if ($productId) {
            $query->where('product_id', $productId);
        }

        return Cache::remember("images_stats" . ($productId ? "_{$productId}" : ""), 3600, function () use ($query) {
            return [
                'total' => $query->count(),
                'active' => $query->where('is_active', true)->count(),
                'primary' => $query->where('is_primary', true)->count(),
                'with_alt_text' => $query->whereNotNull('alt_text')->orWhereNotNull('alt_text_en')->count(),
                'with_caption' => $query->whereNotNull('caption')->orWhereNotNull('caption_en')->count(),
                'large_files' => $query->where('file_size', '>', 1024 * 1024)->count(),
                'total_size' => $query->sum('file_size'),
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        $productId = $this->product_id;
        
        Cache::forget("product_images_count_{$productId}");
        Cache::forget("product_active_images_count_{$productId}");
        Cache::forget("product_primary_image_{$productId}");
        Cache::forget("images_stats_{$productId}");
        Cache::forget("images_stats");
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($image) {
            if (!$image->sort_order) {
                $maxOrder = static::where('product_id', $image->product_id)->max('sort_order');
                $image->sort_order = ($maxOrder ?? 0) + 1;
            }
            
            if (!isset($image->is_active)) {
                $image->is_active = true;
            }
            
            if (!isset($image->is_primary)) {
                $image->is_primary = false;
            }
        });

        static::created(function ($image) {
            $image->clearCache();
        });

        static::updated(function ($image) {
            $image->clearCache();
        });

        static::deleted(function ($image) {
            $image->clearCache();
        });
    }
}