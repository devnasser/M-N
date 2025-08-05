<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'slug',
        'description',
        'description_en',
        'image',
        'icon',
        'color',
        'parent_id',
        'sort_order',
        'is_active',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
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

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function allProducts()
    {
        return $this->products()->with('category');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getProductCount(): int
    {
        return $this->products()->count();
    }

    public function getIconClass(): string
    {
        return $this->icon ?? 'fas fa-cog';
    }

    public function getColorClass(): string
    {
        return $this->color ?? 'bg-primary';
    }

    public function getImageUrl(): string
    {
        return $this->image 
            ? asset('storage/' . $this->image)
            : asset('images/default-category.png');
    }

    public function getLocalizedName(): string
    {
        return app()->getLocale() === 'ar' ? $this->name : $this->name_en;
    }

    public function getLocalizedDescription(): string
    {
        return app()->getLocale() === 'ar' ? $this->description : $this->description_en;
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

    public function getAllProductIds(): array
    {
        $ids = $this->products()->pluck('id')->toArray();
        
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getAllProductIds());
        }
        
        return $ids;
    }
} 