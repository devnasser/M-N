<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'slug',
        'description',
        'description_en',
        'parent_id',
        'level',
        'sort_order',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'image',
        'icon',
        'banner_image',
        'color',
        'products_count',
        'view_count',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'level' => 'integer',
        'sort_order' => 'integer',
        'products_count' => 'integer',
        'view_count' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function subcategories()
    {
        return $this->children();
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function allProducts()
    {
        return $this->products()->with('category');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', $level);
    }

    public function scopeByParent(Builder $query, int $parentId): Builder
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('products_count', 'desc')->limit($limit);
    }

    public function scopeMostViewed(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('view_count', 'desc')->limit($limit);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('name_en', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('description_en', 'like', "%{$search}%");
        });
    }

    // Helper Methods
    public function getLocalizedName(): string
    {
        return app()->getLocale() === 'ar' ? $this->name : $this->name_en;
    }

    public function getLocalizedDescription(): string
    {
        return app()->getLocale() === 'ar' ? $this->description : $this->description_en;
    }

    public function getStatusBadge(): string
    {
        if (!$this->is_active) {
            return '<span class="badge bg-danger">غير نشط</span>';
        }
        
        if ($this->is_featured) {
            return '<span class="badge bg-primary">مميز</span>';
        }
        
        return '<span class="badge bg-success">نشط</span>';
    }

    public function getLevelBadge(): string
    {
        $badges = [
            0 => '<span class="badge bg-primary">رئيسي</span>',
            1 => '<span class="badge bg-info">فرعي</span>',
            2 => '<span class="badge bg-warning">فرعي فرعي</span>',
            3 => '<span class="badge bg-secondary">فرعي فرعي فرعي</span>',
        ];
        
        return $badges[$this->level] ?? '<span class="badge bg-secondary">مستوى ' . $this->level . '</span>';
    }

    public function getImageUrl(): string
    {
        if (!$this->image) {
            return '/images/placeholder-category.jpg';
        }
        
        return $this->image;
    }

    public function getBannerImageUrl(): string
    {
        if (!$this->banner_image) {
            return '/images/placeholder-banner.jpg';
        }
        
        return $this->banner_image;
    }

    public function getIconClass(): string
    {
        return $this->icon ?? 'fas fa-folder';
    }

    public function getColorClass(): string
    {
        $colors = [
            'primary' => 'text-primary',
            'secondary' => 'text-secondary',
            'success' => 'text-success',
            'danger' => 'text-danger',
            'warning' => 'text-warning',
            'info' => 'text-info',
            'light' => 'text-light',
            'dark' => 'text-dark',
        ];
        
        return $colors[$this->color] ?? 'text-primary';
    }

    public function getBackgroundColorClass(): string
    {
        $colors = [
            'primary' => 'bg-primary',
            'secondary' => 'bg-secondary',
            'success' => 'bg-success',
            'danger' => 'bg-danger',
            'warning' => 'bg-warning',
            'info' => 'bg-info',
            'light' => 'bg-light',
            'dark' => 'bg-dark',
        ];
        
        return $colors[$this->color] ?? 'bg-primary';
    }

    public function getProductsCountFormatted(): string
    {
        if ($this->products_count == 0) {
            return 'لا توجد منتجات';
        }
        
        if ($this->products_count == 1) {
            return 'منتج واحد';
        }
        
        if ($this->products_count == 2) {
            return 'منتجان';
        }
        
        if ($this->products_count >= 3 && $this->products_count <= 10) {
            return $this->products_count . ' منتجات';
        }
        
        return $this->products_count . ' منتج';
    }

    public function getViewCountFormatted(): string
    {
        if ($this->view_count == 0) {
            return 'لا توجد مشاهدات';
        }
        
        if ($this->view_count < 1000) {
            return $this->view_count . ' مشاهدة';
        }
        
        if ($this->view_count < 1000000) {
            return number_format($this->view_count / 1000, 1) . ' ألف مشاهدة';
        }
        
        return number_format($this->view_count / 1000000, 1) . ' مليون مشاهدة';
    }

    public function getBreadcrumb(): array
    {
        $breadcrumb = [];
        $current = $this;
        
        while ($current) {
            array_unshift($breadcrumb, $current);
            $current = $current->parent;
        }
        
        return $breadcrumb;
    }

    public function getBreadcrumbText(): string
    {
        $breadcrumb = $this->getBreadcrumb();
        $names = array_map(fn($cat) => $cat->getLocalizedName(), $breadcrumb);
        
        return implode(' > ', $names);
    }

    public function getFullPath(): string
    {
        $breadcrumb = $this->getBreadcrumb();
        $slugs = array_map(fn($cat) => $cat->slug, $breadcrumb);
        
        return implode('/', $slugs);
    }

    public function getUrl(): string
    {
        return route('categories.show', $this->slug);
    }

    public function getEditUrl(): string
    {
        return route('admin.categories.edit', $this->id);
    }

    public function getDeleteUrl(): string
    {
        return route('admin.categories.destroy', $this->id);
    }

    // Business Logic Methods
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function isChild(): bool
    {
        return !is_null($this->parent_id);
    }

    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    public function hasProducts(): bool
    {
        return $this->products_count > 0;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    public function getDepth(): int
    {
        $depth = 0;
        $current = $this;
        
        while ($current->parent) {
            $depth++;
            $current = $current->parent;
        }
        
        return $depth;
    }

    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;
        
        while ($current) {
            array_unshift($ancestors, $current);
            $current = $current->parent;
        }
        
        return $ancestors;
    }

    public function getDescendants(): array
    {
        $descendants = [];
        
        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getDescendants());
        }
        
        return $descendants;
    }

    public function getAllProducts(): \Illuminate\Database\Eloquent\Collection
    {
        $productIds = [$this->id];
        
        // Get all descendant category IDs
        $descendants = $this->getDescendants();
        foreach ($descendants as $descendant) {
            $productIds[] = $descendant->id;
        }
        
        return Product::whereIn('category_id', $productIds)->get();
    }

    public function getAllProductsCount(): int
    {
        $categoryIds = [$this->id];
        
        // Get all descendant category IDs
        $descendants = $this->getDescendants();
        foreach ($descendants as $descendant) {
            $categoryIds[] = $descendant->id;
        }
        
        return Product::whereIn('category_id', $categoryIds)->count();
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function updateProductsCount(): void
    {
        $count = $this->products()->count();
        $this->update(['products_count' => $count]);
    }

    public function makeRoot(): void
    {
        $this->update([
            'parent_id' => null,
            'level' => 0,
        ]);
    }

    public function moveToParent(Category $parent): void
    {
        $this->update([
            'parent_id' => $parent->id,
            'level' => $parent->level + 1,
        ]);
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function feature(): void
    {
        $this->update(['is_featured' => true]);
    }

    public function unfeature(): void
    {
        $this->update(['is_featured' => false]);
    }

    public function moveUp(): void
    {
        $previous = static::where('sort_order', '<', $this->sort_order)
            ->where('parent_id', $this->parent_id)
            ->orderBy('sort_order', 'desc')
            ->first();
        
        if ($previous) {
            $this->update(['sort_order' => $previous->sort_order]);
            $previous->update(['sort_order' => $this->sort_order]);
        }
    }

    public function moveDown(): void
    {
        $next = static::where('sort_order', '>', $this->sort_order)
            ->where('parent_id', $this->parent_id)
            ->orderBy('sort_order', 'asc')
            ->first();
        
        if ($next) {
            $this->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $this->sort_order]);
        }
    }

    public function getSiblings(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('parent_id', $this->parent_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

    public function getPreviousSibling(): ?Category
    {
        return static::where('parent_id', $this->parent_id)
            ->where('sort_order', '<', $this->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();
    }

    public function getNextSibling(): ?Category
    {
        return static::where('parent_id', $this->parent_id)
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();
    }

    public function getTree(): array
    {
        $tree = [
            'id' => $this->id,
            'name' => $this->getLocalizedName(),
            'slug' => $this->slug,
            'level' => $this->level,
            'products_count' => $this->products_count,
            'children' => [],
        ];
        
        foreach ($this->children as $child) {
            $tree['children'][] = $child->getTree();
        }
        
        return $tree;
    }

    // Static Methods
    public static function getTreeStructure(): array
    {
        $rootCategories = static::root()->with('allChildren')->get();
        $tree = [];
        
        foreach ($rootCategories as $category) {
            $tree[] = $category->getTree();
        }
        
        return $tree;
    }

    public static function getActiveTree(): array
    {
        $rootCategories = static::root()->active()->with('allChildren')->get();
        $tree = [];
        
        foreach ($rootCategories as $category) {
            $tree[] = $category->getTree();
        }
        
        return $tree;
    }

    public static function getFeaturedCategories(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::featured()->active()->ordered()->limit($limit)->get();
    }

    public static function getPopularCategories(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->popular($limit)->get();
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($category) {
            // Set default values
            if (is_null($category->is_active)) {
                $category->is_active = true;
            }
            
            if (is_null($category->is_featured)) {
                $category->is_featured = false;
            }
            
            if (is_null($category->level)) {
                $category->level = $category->parent ? $category->parent->level + 1 : 0;
            }
            
            if (is_null($category->sort_order)) {
                $maxOrder = static::where('parent_id', $category->parent_id)->max('sort_order');
                $category->sort_order = ($maxOrder ?? 0) + 1;
            }
            
            if (is_null($category->products_count)) {
                $category->products_count = 0;
            }
            
            if (is_null($category->view_count)) {
                $category->view_count = 0;
            }
            
            // Generate slug if not provided
            if (empty($category->slug)) {
                $category->slug = \Str::slug($category->name);
            }
        });

        static::created(function ($category) {
            // Clear cache
            Cache::forget('categories_tree');
            Cache::forget('active_categories_tree');
            Cache::forget('featured_categories');
        });

        static::updated(function ($category) {
            // Clear cache
            Cache::forget('categories_tree');
            Cache::forget('active_categories_tree');
            Cache::forget('featured_categories');
        });

        static::deleted(function ($category) {
            // Clear cache
            Cache::forget('categories_tree');
            Cache::forget('active_categories_tree');
            Cache::forget('featured_categories');
        });
    }
} 