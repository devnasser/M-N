<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'title',
        'title_en',
        'comment',
        'comment_en',
        'pros',
        'cons',
        'images',
        'videos',
        'is_verified',
        'is_featured',
        'is_helpful',
        'helpful_count',
        'not_helpful_count',
        'status',
        'moderated_at',
        'moderated_by',
        'moderation_notes',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'integer',
        'pros' => 'array',
        'cons' => 'array',
        'images' => 'array',
        'videos' => 'array',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'is_helpful' => 'boolean',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'moderated_at' => 'datetime',
        'metadata' => 'array',
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

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function replies()
    {
        return $this->hasMany(ReviewReply::class);
    }

    public function helpfulVotes()
    {
        return $this->hasMany(ReviewVote::class)->where('vote_type', 'helpful');
    }

    public function notHelpfulVotes()
    {
        return $this->hasMany(ReviewVote::class)->where('vote_type', 'not_helpful');
    }

    public function reports()
    {
        return $this->hasMany(ReviewReport::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeByRating(Builder $query, int $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByReviewable(Builder $query, string $type, int $id): Builder
    {
        return $query->where('reviewable_type', $type)->where('reviewable_id', $id);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeMostHelpful(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('helpful_count', 'desc')->limit($limit);
    }

    public function scopeTopRated(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('rating', 'desc')->limit($limit);
    }

    public function scopeLowRated(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('rating', 'asc')->limit($limit);
    }

    public function scopeWithImages(Builder $query): Builder
    {
        return $query->whereNotNull('images')->where('images', '!=', '[]');
    }

    public function scopeWithVideos(Builder $query): Builder
    {
        return $query->whereNotNull('videos')->where('videos', '!=', '[]');
    }

    // Helper Methods
    public function getLocalizedTitle(): string
    {
        return app()->getLocale() === 'ar' ? $this->title : $this->title_en;
    }

    public function getLocalizedComment(): string
    {
        return app()->getLocale() === 'ar' ? $this->comment : $this->comment_en;
    }

    public function getStatusBadge(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">في الانتظار</span>',
            'approved' => '<span class="badge bg-success">مقبول</span>',
            'rejected' => '<span class="badge bg-danger">مرفوض</span>',
            'spam' => '<span class="badge bg-secondary">رسائل مزعجة</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge bg-secondary">' . $this->status . '</span>';
    }

    public function getVerificationBadge(): string
    {
        if ($this->is_verified) {
            return '<span class="badge bg-success">موثق</span>';
        }
        
        return '<span class="badge bg-warning">غير موثق</span>';
    }

    public function getFeaturedBadge(): string
    {
        if ($this->is_featured) {
            return '<span class="badge bg-primary">مميز</span>';
        }
        
        return '';
    }

    public function getRatingStars(): string
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $stars .= '<i class="fas fa-star text-warning"></i>';
            } else {
                $stars .= '<i class="far fa-star text-muted"></i>';
            }
        }
        
        return $stars . ' <span class="ms-1">(' . $this->rating . '/5)</span>';
    }

    public function getRatingText(): string
    {
        $texts = [
            1 => 'سيء جداً',
            2 => 'سيء',
            3 => 'متوسط',
            4 => 'جيد',
            5 => 'ممتاز',
        ];
        
        return $texts[$this->rating] ?? 'غير محدد';
    }

    public function getRatingColor(): string
    {
        $colors = [
            1 => 'text-danger',
            2 => 'text-warning',
            3 => 'text-info',
            4 => 'text-primary',
            5 => 'text-success',
        ];
        
        return $colors[$this->rating] ?? 'text-muted';
    }

    public function getImages(): array
    {
        return $this->images ?? [];
    }

    public function getVideos(): array
    {
        return $this->videos ?? [];
    }

    public function getPros(): array
    {
        return $this->pros ?? [];
    }

    public function getCons(): array
    {
        return $this->cons ?? [];
    }

    public function getProsText(): string
    {
        $pros = $this->getPros();
        
        if (empty($pros)) {
            return 'لا توجد إيجابيات محددة';
        }
        
        return implode(', ', $pros);
    }

    public function getConsText(): string
    {
        $cons = $this->getCons();
        
        if (empty($cons)) {
            return 'لا توجد سلبيات محددة';
        }
        
        return implode(', ', $cons);
    }

    public function getHelpfulPercentage(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        
        if ($total == 0) {
            return 0;
        }
        
        return round(($this->helpful_count / $total) * 100, 2);
    }

    public function getHelpfulPercentageFormatted(): string
    {
        return $this->getHelpfulPercentage() . '%';
    }

    public function getTotalVotes(): int
    {
        return $this->helpful_count + $this->not_helpful_count;
    }

    public function getTotalVotesFormatted(): string
    {
        $total = $this->getTotalVotes();
        
        if ($total == 0) {
            return 'لا توجد أصوات';
        }
        
        if ($total == 1) {
            return 'صوت واحد';
        }
        
        if ($total == 2) {
            return 'صوتان';
        }
        
        if ($total >= 3 && $total <= 10) {
            return $total . ' أصوات';
        }
        
        return $total . ' صوت';
    }

    public function getModeratedAtFormatted(): string
    {
        if (!$this->moderated_at) {
            return 'غير محدد';
        }
        
        return $this->moderated_at->format('Y-m-d H:i');
    }

    public function getModeratorName(): string
    {
        return $this->moderator->name ?? 'غير محدد';
    }

    public function getReviewableName(): string
    {
        if ($this->reviewable) {
            if (method_exists($this->reviewable, 'getLocalizedName')) {
                return $this->reviewable->getLocalizedName();
            }
            
            if (method_exists($this->reviewable, 'name')) {
                return $this->reviewable->name;
            }
        }
        
        return 'غير محدد';
    }

    public function getReviewableTypeName(): string
    {
        $types = [
            'App\Models\Product' => 'منتج',
            'App\Models\Brand' => 'علامة تجارية',
            'App\Models\Technician' => 'فني',
            'App\Models\Order' => 'طلب',
        ];
        
        return $types[$this->reviewable_type] ?? $this->reviewable_type;
    }

    public function getReviewableUrl(): string
    {
        if (!$this->reviewable) {
            return '#';
        }
        
        if (method_exists($this->reviewable, 'getUrl')) {
            return $this->reviewable->getUrl();
        }
        
        return '#';
    }

    // Business Logic Methods
    public function isActive(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    public function hasImages(): bool
    {
        return !empty($this->getImages());
    }

    public function hasVideos(): bool
    {
        return !empty($this->getVideos());
    }

    public function hasPros(): bool
    {
        return !empty($this->getPros());
    }

    public function hasCons(): bool
    {
        return !empty($this->getCons());
    }

    public function canBeEdited(): bool
    {
        return $this->isActive() && $this->created_at->diffInHours(now()) <= 24;
    }

    public function canBeDeleted(): bool
    {
        return $this->created_at->diffInHours(now()) <= 24;
    }

    public function canBeReported(): bool
    {
        return $this->isActive();
    }

    public function approve(int $moderatorId = null, string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'moderated_at' => now(),
            'moderated_by' => $moderatorId,
            'moderation_notes' => $notes,
        ]);
    }

    public function reject(int $moderatorId = null, string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'moderated_at' => now(),
            'moderated_by' => $moderatorId,
            'moderation_notes' => $notes,
        ]);
    }

    public function markAsSpam(int $moderatorId = null, string $notes = null): void
    {
        $this->update([
            'status' => 'spam',
            'moderated_at' => now(),
            'moderated_by' => $moderatorId,
            'moderation_notes' => $notes,
        ]);
    }

    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    public function unverify(): void
    {
        $this->update(['is_verified' => false]);
    }

    public function feature(): void
    {
        $this->update(['is_featured' => true]);
    }

    public function unfeature(): void
    {
        $this->update(['is_featured' => false]);
    }

    public function incrementHelpfulCount(): void
    {
        $this->increment('helpful_count');
    }

    public function decrementHelpfulCount(): void
    {
        $this->decrement('helpful_count');
    }

    public function incrementNotHelpfulCount(): void
    {
        $this->increment('not_helpful_count');
    }

    public function decrementNotHelpfulCount(): void
    {
        $this->decrement('not_helpful_count');
    }

    public function addPros(array $pros): void
    {
        $currentPros = $this->getPros();
        $newPros = array_merge($currentPros, $pros);
        $this->update(['pros' => array_unique($newPros)]);
    }

    public function removePros(array $pros): void
    {
        $currentPros = $this->getPros();
        $newPros = array_diff($currentPros, $pros);
        $this->update(['pros' => array_values($newPros)]);
    }

    public function addCons(array $cons): void
    {
        $currentCons = $this->getCons();
        $newCons = array_merge($currentCons, $cons);
        $this->update(['cons' => array_unique($newCons)]);
    }

    public function removeCons(array $cons): void
    {
        $currentCons = $this->getCons();
        $newCons = array_diff($currentCons, $cons);
        $this->update(['cons' => array_values($newCons)]);
    }

    public function addImage(string $image): void
    {
        $images = $this->getImages();
        $images[] = $image;
        $this->update(['images' => array_unique($images)]);
    }

    public function removeImage(string $image): void
    {
        $images = $this->getImages();
        $images = array_diff($images, [$image]);
        $this->update(['images' => array_values($images)]);
    }

    public function addVideo(string $video): void
    {
        $videos = $this->getVideos();
        $videos[] = $video;
        $this->update(['videos' => array_unique($videos)]);
    }

    public function removeVideo(string $video): void
    {
        $videos = $this->getVideos();
        $videos = array_diff($videos, [$video]);
        $this->update(['videos' => array_values($videos)]);
    }

    public function getAverageRating(): float
    {
        return $this->rating;
    }

    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

    public function isNeutral(): bool
    {
        return $this->rating == 3;
    }

    public function getSentiment(): string
    {
        if ($this->isPositive()) {
            return 'positive';
        }
        
        if ($this->isNegative()) {
            return 'negative';
        }
        
        return 'neutral';
    }

    public function getSentimentBadge(): string
    {
        $sentiment = $this->getSentiment();
        
        $badges = [
            'positive' => '<span class="badge bg-success">إيجابي</span>',
            'neutral' => '<span class="badge bg-warning">محايد</span>',
            'negative' => '<span class="badge bg-danger">سلبي</span>',
        ];
        
        return $badges[$sentiment] ?? $badges['neutral'];
    }

    // Static Methods
    public static function getAverageRatingForReviewable(string $type, int $id): float
    {
        return static::active()
            ->byReviewable($type, $id)
            ->avg('rating') ?? 0;
    }

    public static function getRatingDistributionForReviewable(string $type, int $id): array
    {
        $distribution = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $count = static::active()
                ->byReviewable($type, $id)
                ->byRating($i)
                ->count();
            
            $distribution[$i] = $count;
        }
        
        return $distribution;
    }

    public static function getTotalReviewsForReviewable(string $type, int $id): int
    {
        return static::active()
            ->byReviewable($type, $id)
            ->count();
    }

    public static function getVerifiedReviewsForReviewable(string $type, int $id): int
    {
        return static::active()
            ->verified()
            ->byReviewable($type, $id)
            ->count();
    }

    public static function getFeaturedReviewsForReviewable(string $type, int $id): int
    {
        return static::active()
            ->featured()
            ->byReviewable($type, $id)
            ->count();
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($review) {
            // Set default values
            if (is_null($review->status)) {
                $review->status = 'pending';
            }
            
            if (is_null($review->is_verified)) {
                $review->is_verified = false;
            }
            
            if (is_null($review->is_featured)) {
                $review->is_featured = false;
            }
            
            if (is_null($review->helpful_count)) {
                $review->helpful_count = 0;
            }
            
            if (is_null($review->not_helpful_count)) {
                $review->not_helpful_count = 0;
            }
        });

        static::created(function ($review) {
            // Update reviewable rating
            if ($review->reviewable && method_exists($review->reviewable, 'updateRating')) {
                $review->reviewable->updateRating();
            }
            
            // Clear cache
            Cache::forget("reviewable_rating_{$review->reviewable_type}_{$review->reviewable_id}");
            Cache::forget("reviewable_reviews_count_{$review->reviewable_type}_{$review->reviewable_id}");
        });

        static::updated(function ($review) {
            // Update reviewable rating if rating changed
            if ($review->wasChanged('rating') && $review->reviewable && method_exists($review->reviewable, 'updateRating')) {
                $review->reviewable->updateRating();
            }
            
            // Clear cache
            Cache::forget("reviewable_rating_{$review->reviewable_type}_{$review->reviewable_id}");
            Cache::forget("reviewable_reviews_count_{$review->reviewable_type}_{$review->reviewable_id}");
        });

        static::deleted(function ($review) {
            // Update reviewable rating
            if ($review->reviewable && method_exists($review->reviewable, 'updateRating')) {
                $review->reviewable->updateRating();
            }
            
            // Clear cache
            Cache::forget("reviewable_rating_{$review->reviewable_type}_{$review->reviewable_id}");
            Cache::forget("reviewable_reviews_count_{$review->reviewable_type}_{$review->reviewable_id}");
        });
    }
} 