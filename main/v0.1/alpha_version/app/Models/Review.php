<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comment',
        'comment_en',
        'status',
        'is_verified',
        'is_helpful',
        'images',
        'meta_data',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified' => 'boolean',
        'is_helpful' => 'boolean',
        'images' => 'array',
        'meta_data' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewable()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeHelpful($query)
    {
        return $query->where('is_helpful', true);
    }

    // Helper Methods
    public function getRatingStars(): string
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $stars .= '<i class="fas fa-star text-warning"></i>';
            } else {
                $stars .= '<i class="far fa-star text-warning"></i>';
            }
        }
        return $stars;
    }

    public function getStatusBadge(): string
    {
        $badges = [
            'pending' => 'bg-warning',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
        ];

        $labels = [
            'pending' => 'في الانتظار',
            'approved' => 'مقبول',
            'rejected' => 'مرفوض',
        ];

        $color = $badges[$this->status] ?? 'bg-secondary';
        $label = $labels[$this->status] ?? $this->status;

        return "<span class=\"badge {$color}\">{$label}</span>";
    }

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function isHelpful(): bool
    {
        return $this->is_helpful;
    }

    public function getLocalizedComment(): string
    {
        return app()->getLocale() === 'ar' ? $this->comment : $this->comment_en;
    }
} 