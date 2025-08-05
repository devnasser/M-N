<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_en',
        'description',
        'description_en',
        'type',
        'category',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'mime_type',
        'version',
        'status',
        'is_public',
        'is_featured',
        'is_required',
        'expires_at',
        'published_at',
        'archived_at',
        'download_count',
        'view_count',
        'last_downloaded_at',
        'last_viewed_at',
        'uploaded_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'tags',
        'metadata',
    ];

    protected $hidden = [
        'metadata',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expires_at' => 'datetime',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'view_count' => 'integer',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'is_required' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Type constants
    const TYPE_CONTRACT = 'contract';
    const TYPE_INVOICE = 'invoice';
    const TYPE_CERTIFICATE = 'certificate';
    const TYPE_LICENSE = 'license';
    const TYPE_PERMIT = 'permit';
    const TYPE_INSURANCE = 'insurance';
    const TYPE_WARRANTY = 'warranty';
    const TYPE_MANUAL = 'manual';
    const TYPE_CATALOG = 'catalog';
    const TYPE_BROCHURE = 'brochure';
    const TYPE_POLICY = 'policy';
    const TYPE_PROCEDURE = 'procedure';
    const TYPE_FORM = 'form';
    const TYPE_REPORT = 'report';
    const TYPE_AGREEMENT = 'agreement';
    const TYPE_OTHER = 'other';

    // Category constants
    const CATEGORY_LEGAL = 'legal';
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_TECHNICAL = 'technical';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_OPERATIONAL = 'operational';
    const CATEGORY_COMPLIANCE = 'compliance';
    const CATEGORY_TRAINING = 'training';
    const CATEGORY_REFERENCE = 'reference';

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_EXPIRED = 'expired';

    // File type constants
    const FILE_TYPE_PDF = 'pdf';
    const FILE_TYPE_DOC = 'doc';
    const FILE_TYPE_DOCX = 'docx';
    const FILE_TYPE_XLS = 'xls';
    const FILE_TYPE_XLSX = 'xlsx';
    const FILE_TYPE_PPT = 'ppt';
    const FILE_TYPE_PPTX = 'pptx';
    const FILE_TYPE_TXT = 'txt';
    const FILE_TYPE_CSV = 'csv';
    const FILE_TYPE_JSON = 'json';
    const FILE_TYPE_XML = 'xml';
    const FILE_TYPE_HTML = 'html';
    const FILE_TYPE_IMAGE = 'image';
    const FILE_TYPE_VIDEO = 'video';
    const FILE_TYPE_AUDIO = 'audio';
    const FILE_TYPE_ARCHIVE = 'archive';

    // Relationships
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function downloads()
    {
        return $this->hasMany(DocumentDownload::class);
    }

    public function views()
    {
        return $this->hasMany(DocumentView::class);
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Scopes
    public function scopeByType(Builder $query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory(Builder $query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByFileType(Builder $query, $fileType)
    {
        return $query->where('file_type', $fileType);
    }

    public function scopeByUploader(Builder $query, $uploaderId)
    {
        return $query->where('uploaded_by', $uploaderId);
    }

    public function scopeByApprover(Builder $query, $approverId)
    {
        return $query->where('approved_by', $approverId);
    }

    public function scopeByTag(Builder $query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopePublic(Builder $query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate(Builder $query)
    {
        return $query->where('is_public', false);
    }

    public function scopeFeatured(Builder $query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRequired(Builder $query)
    {
        return $query->where('is_required', true);
    }

    public function scopeDraft(Builder $query)
    {
        return $query->where('status', self::STATUS_DRAFT);
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

    public function scopePublished(Builder $query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeArchived(Builder $query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopeExpired(Builder $query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
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

    public function scopeRecent(Builder $query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopePopular(Builder $query, $limit = 10)
    {
        return $query->orderBy('download_count', 'desc')->limit($limit);
    }

    public function scopeMostViewed(Builder $query, $limit = 10)
    {
        return $query->orderBy('view_count', 'desc')->limit($limit);
    }

    public function scopeLargeFiles(Builder $query, $threshold = 1024 * 1024) // 1MB
    {
        return $query->where('file_size', '>', $threshold);
    }

    public function scopeSmallFiles(Builder $query, $threshold = 100 * 1024) // 100KB
    {
        return $query->where('file_size', '<', $threshold);
    }

    public function scopeExpiringSoon(Builder $query, $days = 30)
    {
        return $query->where('expires_at', '<=', Carbon::now()->addDays($days))
                    ->where('expires_at', '>', Carbon::now());
    }

    public function scopeExpired(Builder $query)
    {
        return $query->where('expires_at', '<', Carbon::now());
    }

    // Helper Methods
    public function getTitleAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->title) {
            return $this->title;
        }
        return $value ?: $this->title_en;
    }

    public function getDescriptionAttribute($value)
    {
        if (app()->getLocale() === 'ar' && $this->description) {
            return $this->description;
        }
        return $value ?: $this->description_en;
    }

    public function getTitleArAttribute()
    {
        return $this->title;
    }

    public function getDescriptionArAttribute()
    {
        return $this->description;
    }

    public function getTitleEnAttribute()
    {
        return $this->title_en;
    }

    public function getDescriptionEnAttribute()
    {
        return $this->description_en;
    }

    public function getTypeNameAttribute()
    {
        switch ($this->type) {
            case self::TYPE_CONTRACT:
                return 'Contract';
            case self::TYPE_INVOICE:
                return 'Invoice';
            case self::TYPE_CERTIFICATE:
                return 'Certificate';
            case self::TYPE_LICENSE:
                return 'License';
            case self::TYPE_PERMIT:
                return 'Permit';
            case self::TYPE_INSURANCE:
                return 'Insurance';
            case self::TYPE_WARRANTY:
                return 'Warranty';
            case self::TYPE_MANUAL:
                return 'Manual';
            case self::TYPE_CATALOG:
                return 'Catalog';
            case self::TYPE_BROCHURE:
                return 'Brochure';
            case self::TYPE_POLICY:
                return 'Policy';
            case self::TYPE_PROCEDURE:
                return 'Procedure';
            case self::TYPE_FORM:
                return 'Form';
            case self::TYPE_REPORT:
                return 'Report';
            case self::TYPE_AGREEMENT:
                return 'Agreement';
            case self::TYPE_OTHER:
                return 'Other';
            default:
                return ucfirst($this->type);
        }
    }

    public function getTypeNameArAttribute()
    {
        switch ($this->type) {
            case self::TYPE_CONTRACT:
                return 'عقد';
            case self::TYPE_INVOICE:
                return 'فاتورة';
            case self::TYPE_CERTIFICATE:
                return 'شهادة';
            case self::TYPE_LICENSE:
                return 'ترخيص';
            case self::TYPE_PERMIT:
                return 'إذن';
            case self::TYPE_INSURANCE:
                return 'تأمين';
            case self::TYPE_WARRANTY:
                return 'ضمان';
            case self::TYPE_MANUAL:
                return 'دليل';
            case self::TYPE_CATALOG:
                return 'كتالوج';
            case self::TYPE_BROCHURE:
                return 'نشرة';
            case self::TYPE_POLICY:
                return 'سياسة';
            case self::TYPE_PROCEDURE:
                return 'إجراء';
            case self::TYPE_FORM:
                return 'نموذج';
            case self::TYPE_REPORT:
                return 'تقرير';
            case self::TYPE_AGREEMENT:
                return 'اتفاقية';
            case self::TYPE_OTHER:
                return 'أخرى';
            default:
                return ucfirst($this->type);
        }
    }

    public function getTypeIconAttribute()
    {
        switch ($this->type) {
            case self::TYPE_CONTRACT:
                return 'fas fa-file-contract';
            case self::TYPE_INVOICE:
                return 'fas fa-file-invoice';
            case self::TYPE_CERTIFICATE:
                return 'fas fa-certificate';
            case self::TYPE_LICENSE:
                return 'fas fa-id-card';
            case self::TYPE_PERMIT:
                return 'fas fa-passport';
            case self::TYPE_INSURANCE:
                return 'fas fa-shield-alt';
            case self::TYPE_WARRANTY:
                return 'fas fa-shield-check';
            case self::TYPE_MANUAL:
                return 'fas fa-book';
            case self::TYPE_CATALOG:
                return 'fas fa-list-alt';
            case self::TYPE_BROCHURE:
                return 'fas fa-file-alt';
            case self::TYPE_POLICY:
                return 'fas fa-file-signature';
            case self::TYPE_PROCEDURE:
                return 'fas fa-tasks';
            case self::TYPE_FORM:
                return 'fas fa-clipboard-list';
            case self::TYPE_REPORT:
                return 'fas fa-chart-bar';
            case self::TYPE_AGREEMENT:
                return 'fas fa-handshake';
            case self::TYPE_OTHER:
                return 'fas fa-file';
            default:
                return 'fas fa-file';
        }
    }

    public function getCategoryNameAttribute()
    {
        switch ($this->category) {
            case self::CATEGORY_LEGAL:
                return 'Legal';
            case self::CATEGORY_FINANCIAL:
                return 'Financial';
            case self::CATEGORY_TECHNICAL:
                return 'Technical';
            case self::CATEGORY_MARKETING:
                return 'Marketing';
            case self::CATEGORY_OPERATIONAL:
                return 'Operational';
            case self::CATEGORY_COMPLIANCE:
                return 'Compliance';
            case self::CATEGORY_TRAINING:
                return 'Training';
            case self::CATEGORY_REFERENCE:
                return 'Reference';
            default:
                return ucfirst($this->category);
        }
    }

    public function getCategoryNameArAttribute()
    {
        switch ($this->category) {
            case self::CATEGORY_LEGAL:
                return 'قانوني';
            case self::CATEGORY_FINANCIAL:
                return 'مالي';
            case self::CATEGORY_TECHNICAL:
                return 'تقني';
            case self::CATEGORY_MARKETING:
                return 'تسويقي';
            case self::CATEGORY_OPERATIONAL:
                return 'تشغيلي';
            case self::CATEGORY_COMPLIANCE:
                return 'امتثال';
            case self::CATEGORY_TRAINING:
                return 'تدريبي';
            case self::CATEGORY_REFERENCE:
                return 'مرجعي';
            default:
                return ucfirst($this->category);
        }
    }

    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case self::STATUS_DRAFT:
                return 'Draft';
            case self::STATUS_PENDING:
                return 'Pending';
            case self::STATUS_APPROVED:
                return 'Approved';
            case self::STATUS_REJECTED:
                return 'Rejected';
            case self::STATUS_PUBLISHED:
                return 'Published';
            case self::STATUS_ARCHIVED:
                return 'Archived';
            case self::STATUS_EXPIRED:
                return 'Expired';
            default:
                return 'Unknown';
        }
    }

    public function getStatusNameArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_DRAFT:
                return 'مسودة';
            case self::STATUS_PENDING:
                return 'في الانتظار';
            case self::STATUS_APPROVED:
                return 'موافق عليه';
            case self::STATUS_REJECTED:
                return 'مرفوض';
            case self::STATUS_PUBLISHED:
                return 'منشور';
            case self::STATUS_ARCHIVED:
                return 'مؤرشف';
            case self::STATUS_EXPIRED:
                return 'منتهي الصلاحية';
            default:
                return 'غير معروف';
        }
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case self::STATUS_DRAFT:
                return '<span class="badge bg-secondary">Draft</span>';
            case self::STATUS_PENDING:
                return '<span class="badge bg-warning">Pending</span>';
            case self::STATUS_APPROVED:
                return '<span class="badge bg-info">Approved</span>';
            case self::STATUS_REJECTED:
                return '<span class="badge bg-danger">Rejected</span>';
            case self::STATUS_PUBLISHED:
                return '<span class="badge bg-success">Published</span>';
            case self::STATUS_ARCHIVED:
                return '<span class="badge bg-dark">Archived</span>';
            case self::STATUS_EXPIRED:
                return '<span class="badge bg-danger">Expired</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    public function getStatusBadgeArAttribute()
    {
        switch ($this->status) {
            case self::STATUS_DRAFT:
                return '<span class="badge bg-secondary">مسودة</span>';
            case self::STATUS_PENDING:
                return '<span class="badge bg-warning">في الانتظار</span>';
            case self::STATUS_APPROVED:
                return '<span class="badge bg-info">موافق عليه</span>';
            case self::STATUS_REJECTED:
                return '<span class="badge bg-danger">مرفوض</span>';
            case self::STATUS_PUBLISHED:
                return '<span class="badge bg-success">منشور</span>';
            case self::STATUS_ARCHIVED:
                return '<span class="badge bg-dark">مؤرشف</span>';
            case self::STATUS_EXPIRED:
                return '<span class="badge bg-danger">منتهي الصلاحية</span>';
            default:
                return '<span class="badge bg-secondary">غير معروف</span>';
        }
    }

    public function getFileTypeNameAttribute()
    {
        switch ($this->file_type) {
            case self::FILE_TYPE_PDF:
                return 'PDF Document';
            case self::FILE_TYPE_DOC:
                return 'Word Document';
            case self::FILE_TYPE_DOCX:
                return 'Word Document';
            case self::FILE_TYPE_XLS:
                return 'Excel Spreadsheet';
            case self::FILE_TYPE_XLSX:
                return 'Excel Spreadsheet';
            case self::FILE_TYPE_PPT:
                return 'PowerPoint Presentation';
            case self::FILE_TYPE_PPTX:
                return 'PowerPoint Presentation';
            case self::FILE_TYPE_TXT:
                return 'Text File';
            case self::FILE_TYPE_CSV:
                return 'CSV File';
            case self::FILE_TYPE_JSON:
                return 'JSON File';
            case self::FILE_TYPE_XML:
                return 'XML File';
            case self::FILE_TYPE_HTML:
                return 'HTML File';
            case self::FILE_TYPE_IMAGE:
                return 'Image File';
            case self::FILE_TYPE_VIDEO:
                return 'Video File';
            case self::FILE_TYPE_AUDIO:
                return 'Audio File';
            case self::FILE_TYPE_ARCHIVE:
                return 'Archive File';
            default:
                return strtoupper($this->file_type) . ' File';
        }
    }

    public function getFileTypeNameArAttribute()
    {
        switch ($this->file_type) {
            case self::FILE_TYPE_PDF:
                return 'مستند PDF';
            case self::FILE_TYPE_DOC:
                return 'مستند Word';
            case self::FILE_TYPE_DOCX:
                return 'مستند Word';
            case self::FILE_TYPE_XLS:
                return 'جدول بيانات Excel';
            case self::FILE_TYPE_XLSX:
                return 'جدول بيانات Excel';
            case self::FILE_TYPE_PPT:
                return 'عرض تقديمي PowerPoint';
            case self::FILE_TYPE_PPTX:
                return 'عرض تقديمي PowerPoint';
            case self::FILE_TYPE_TXT:
                return 'ملف نصي';
            case self::FILE_TYPE_CSV:
                return 'ملف CSV';
            case self::FILE_TYPE_JSON:
                return 'ملف JSON';
            case self::FILE_TYPE_XML:
                return 'ملف XML';
            case self::FILE_TYPE_HTML:
                return 'ملف HTML';
            case self::FILE_TYPE_IMAGE:
                return 'ملف صورة';
            case self::FILE_TYPE_VIDEO:
                return 'ملف فيديو';
            case self::FILE_TYPE_AUDIO:
                return 'ملف صوتي';
            case self::FILE_TYPE_ARCHIVE:
                return 'ملف مضغوط';
            default:
                return 'ملف ' . strtoupper($this->file_type);
        }
    }

    public function getFileTypeIconAttribute()
    {
        switch ($this->file_type) {
            case self::FILE_TYPE_PDF:
                return 'fas fa-file-pdf';
            case self::FILE_TYPE_DOC:
            case self::FILE_TYPE_DOCX:
                return 'fas fa-file-word';
            case self::FILE_TYPE_XLS:
            case self::FILE_TYPE_XLSX:
                return 'fas fa-file-excel';
            case self::FILE_TYPE_PPT:
            case self::FILE_TYPE_PPTX:
                return 'fas fa-file-powerpoint';
            case self::FILE_TYPE_TXT:
                return 'fas fa-file-alt';
            case self::FILE_TYPE_CSV:
                return 'fas fa-file-csv';
            case self::FILE_TYPE_JSON:
            case self::FILE_TYPE_XML:
            case self::FILE_TYPE_HTML:
                return 'fas fa-file-code';
            case self::FILE_TYPE_IMAGE:
                return 'fas fa-file-image';
            case self::FILE_TYPE_VIDEO:
                return 'fas fa-file-video';
            case self::FILE_TYPE_AUDIO:
                return 'fas fa-file-audio';
            case self::FILE_TYPE_ARCHIVE:
                return 'fas fa-file-archive';
            default:
                return 'fas fa-file';
        }
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

    public function getFileUrlAttribute()
    {
        if (!$this->file_path) {
            return null;
        }
        if (filter_var($this->file_path, FILTER_VALIDATE_URL)) {
            return $this->file_path;
        }
        return asset('storage/' . $this->file_path);
    }

    public function getPublishedAtFormattedAttribute()
    {
        return $this->published_at ? $this->published_at->format('M d, Y H:i') : null;
    }

    public function getPublishedAtFormattedArAttribute()
    {
        return $this->published_at ? $this->published_at->format('d M Y H:i') : null;
    }

    public function getArchivedAtFormattedAttribute()
    {
        return $this->archived_at ? $this->archived_at->format('M d, Y H:i') : null;
    }

    public function getArchivedAtFormattedArAttribute()
    {
        return $this->archived_at ? $this->archived_at->format('d M Y H:i') : null;
    }

    public function getLastDownloadedAtFormattedAttribute()
    {
        return $this->last_downloaded_at ? $this->last_downloaded_at->format('M d, Y H:i') : null;
    }

    public function getLastDownloadedAtFormattedArAttribute()
    {
        return $this->last_downloaded_at ? $this->last_downloaded_at->format('d M Y H:i') : null;
    }

    public function getLastViewedAtFormattedAttribute()
    {
        return $this->last_viewed_at ? $this->last_viewed_at->format('M d, Y H:i') : null;
    }

    public function getLastViewedAtFormattedArAttribute()
    {
        return $this->last_viewed_at ? $this->last_viewed_at->format('d M Y H:i') : null;
    }

    public function getApprovedAtFormattedAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('M d, Y H:i') : null;
    }

    public function getApprovedAtFormattedArAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('d M Y H:i') : null;
    }

    public function getRejectedAtFormattedAttribute()
    {
        return $this->rejected_at ? $this->rejected_at->format('M d, Y H:i') : null;
    }

    public function getRejectedAtFormattedArAttribute()
    {
        return $this->rejected_at ? $this->rejected_at->format('d M Y H:i') : null;
    }

    public function getExpiresAtFormattedAttribute()
    {
        return $this->expires_at ? $this->expires_at->format('M d, Y H:i') : null;
    }

    public function getExpiresAtFormattedArAttribute()
    {
        return $this->expires_at ? $this->expires_at->format('d M Y H:i') : null;
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

    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expires_at) {
            return null;
        }
        return Carbon::now()->diffInDays($this->expires_at, false);
    }

    public function getDaysUntilExpiryFormattedAttribute()
    {
        $days = $this->getDaysUntilExpiryAttribute();
        if ($days === null) {
            return null;
        }
        if ($days > 0) {
            return $days . ' days remaining';
        } elseif ($days < 0) {
            return abs($days) . ' days expired';
        } else {
            return 'Expires today';
        }
    }

    public function getDaysUntilExpiryFormattedArAttribute()
    {
        $days = $this->getDaysUntilExpiryAttribute();
        if ($days === null) {
            return null;
        }
        if ($days > 0) {
            return $days . ' يوم متبقي';
        } elseif ($days < 0) {
            return abs($days) . ' يوم منتهي';
        } else {
            return 'ينتهي اليوم';
        }
    }

    public function getTagsListAttribute()
    {
        return $this->tags ? implode(', ', $this->tags) : null;
    }

    public function getTagsListArAttribute()
    {
        return $this->getTagsListAttribute();
    }

    // Business Logic
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

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

    public function isPublished()
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isArchived()
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function isExpired()
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isPublic()
    {
        return $this->is_public;
    }

    public function isFeatured()
    {
        return $this->is_featured;
    }

    public function isRequired()
    {
        return $this->is_required;
    }

    public function hasFile()
    {
        return !empty($this->file_path);
    }

    public function hasDownloads()
    {
        return $this->download_count > 0;
    }

    public function hasViews()
    {
        return $this->view_count > 0;
    }

    public function hasTags()
    {
        return !empty($this->tags);
    }

    public function hasRejectionReason()
    {
        return !empty($this->rejection_reason);
    }

    public function isExpiringSoon($days = 30)
    {
        return $this->expires_at && 
               $this->expires_at <= Carbon::now()->addDays($days) && 
               $this->expires_at > Carbon::now();
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at < Carbon::now();
    }

    public function canBeApproved()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeRejected()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBePublished()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canBeArchived()
    {
        return in_array($this->status, [self::STATUS_PUBLISHED, self::STATUS_APPROVED]);
    }

    public function canBeDownloaded()
    {
        return $this->isPublished() && $this->hasFile() && !$this->isExpired();
    }

    public function canBeViewed()
    {
        return $this->isPublished() && !$this->isExpired();
    }

    public function approve($approvedBy = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => Carbon::now(),
        ]);
        $this->clearCache();
    }

    public function reject($rejectedBy = null, $reason = null)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_by' => $rejectedBy,
            'rejected_at' => Carbon::now(),
            'rejection_reason' => $reason,
        ]);
        $this->clearCache();
    }

    public function publish()
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => Carbon::now(),
        ]);
        $this->clearCache();
    }

    public function archive()
    {
        $this->update([
            'status' => self::STATUS_ARCHIVED,
            'archived_at' => Carbon::now(),
        ]);
        $this->clearCache();
    }

    public function expire()
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
        $this->clearCache();
    }

    public function incrementDownloadCount()
    {
        $this->update([
            'download_count' => $this->download_count + 1,
            'last_downloaded_at' => Carbon::now(),
        ]);
        $this->clearCache();
    }

    public function incrementViewCount()
    {
        $this->update([
            'view_count' => $this->view_count + 1,
            'last_viewed_at' => Carbon::now(),
        ]);
        $this->clearCache();
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

    public function feature()
    {
        $this->update(['is_featured' => true]);
        $this->clearCache();
    }

    public function unfeature()
    {
        $this->update(['is_featured' => false]);
        $this->clearCache();
    }

    public function addTag($tag)
    {
        $tags = $this->tags ?: [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
            $this->clearCache();
        }
    }

    public function removeTag($tag)
    {
        $tags = $this->tags ?: [];
        $tags = array_filter($tags, function ($t) use ($tag) {
            return $t !== $tag;
        });
        $this->update(['tags' => array_values($tags)]);
        $this->clearCache();
    }

    public function hasTag($tag)
    {
        return $this->tags && in_array($tag, $this->tags);
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->status = self::STATUS_DRAFT;
        $duplicate->version = $this->version + 1;
        $duplicate->published_at = null;
        $duplicate->archived_at = null;
        $duplicate->approved_at = null;
        $duplicate->rejected_at = null;
        $duplicate->download_count = 0;
        $duplicate->view_count = 0;
        $duplicate->last_downloaded_at = null;
        $duplicate->last_viewed_at = null;
        $duplicate->approved_by = null;
        $duplicate->rejected_by = null;
        $duplicate->rejection_reason = null;
        $duplicate->save();
        return $duplicate;
    }

    // Static Methods
    public static function getDocumentsCountByType($type)
    {
        return Cache::remember("documents_count_{$type}", 3600, function () use ($type) {
            return static::where('type', $type)->count();
        });
    }

    public static function getDocumentsCountByCategory($category)
    {
        return Cache::remember("documents_count_category_{$category}", 3600, function () use ($category) {
            return static::where('category', $category)->count();
        });
    }

    public static function getPublishedDocumentsCount()
    {
        return Cache::remember('published_documents_count', 3600, function () {
            return static::where('status', self::STATUS_PUBLISHED)->count();
        });
    }

    public static function getPendingDocumentsCount()
    {
        return Cache::remember('pending_documents_count', 3600, function () {
            return static::where('status', self::STATUS_PENDING)->count();
        });
    }

    public static function getExpiringDocumentsCount($days = 30)
    {
        return Cache::remember("expiring_documents_count_{$days}", 3600, function () use ($days) {
            return static::expiringSoon($days)->count();
        });
    }

    public static function getDocumentsByType($type)
    {
        return static::where('type', $type)->orderBy('created_at', 'desc')->get();
    }

    public static function getDocumentsByCategory($category)
    {
        return static::where('category', $category)->orderBy('created_at', 'desc')->get();
    }

    public static function getPublishedDocuments()
    {
        return static::where('status', self::STATUS_PUBLISHED)->orderBy('published_at', 'desc')->get();
    }

    public static function getPendingDocuments()
    {
        return static::where('status', self::STATUS_PENDING)->orderBy('created_at', 'asc')->get();
    }

    public static function getExpiringDocuments($days = 30)
    {
        return static::expiringSoon($days)->orderBy('expires_at', 'asc')->get();
    }

    public static function getPublicDocuments()
    {
        return static::public()->orderBy('created_at', 'desc')->get();
    }

    public static function getFeaturedDocuments()
    {
        return static::featured()->orderBy('created_at', 'desc')->get();
    }

    public static function getRequiredDocuments()
    {
        return static::required()->orderBy('created_at', 'desc')->get();
    }

    public static function getPopularDocuments($limit = 10)
    {
        return static::popular($limit)->get();
    }

    public static function getMostViewedDocuments($limit = 10)
    {
        return static::mostViewed($limit)->get();
    }

    public static function getDocumentsByTag($tag)
    {
        return static::byTag($tag)->orderBy('created_at', 'desc')->get();
    }

    public static function getDocumentsStats($type = null, $category = null)
    {
        $query = static::query();
        if ($type) {
            $query->where('type', $type);
        }
        if ($category) {
            $query->where('category', $category);
        }

        return Cache::remember("documents_stats" . ($type ? "_{$type}" : "") . ($category ? "_{$category}" : ""), 3600, function () use ($query) {
            return [
                'total' => $query->count(),
                'draft' => $query->where('status', self::STATUS_DRAFT)->count(),
                'pending' => $query->where('status', self::STATUS_PENDING)->count(),
                'approved' => $query->where('status', self::STATUS_APPROVED)->count(),
                'rejected' => $query->where('status', self::STATUS_REJECTED)->count(),
                'published' => $query->where('status', self::STATUS_PUBLISHED)->count(),
                'archived' => $query->where('status', self::STATUS_ARCHIVED)->count(),
                'expired' => $query->where('status', self::STATUS_EXPIRED)->count(),
                'public' => $query->where('is_public', true)->count(),
                'featured' => $query->where('is_featured', true)->count(),
                'required' => $query->where('is_required', true)->count(),
                'total_downloads' => $query->sum('download_count'),
                'total_views' => $query->sum('view_count'),
                'total_file_size' => $query->sum('file_size'),
                'average_file_size' => $query->whereNotNull('file_size')->avg('file_size'),
                'expiring_soon' => $query->expiringSoon(30)->count(),
                'success_rate' => $query->count() > 0 ? round(($query->where('status', self::STATUS_PUBLISHED)->count() / $query->count()) * 100, 2) : 0,
            ];
        });
    }

    // Cache Management
    public function clearCache()
    {
        $type = $this->type;
        $category = $this->category;
        Cache::forget("documents_count_{$type}");
        Cache::forget("documents_count_category_{$category}");
        Cache::forget("documents_stats_{$type}");
        Cache::forget("documents_stats_{$category}");
        Cache::forget('published_documents_count');
        Cache::forget('pending_documents_count');
        Cache::forget('expiring_documents_count_30');
        Cache::forget('documents_stats');
    }

    // Booted Events
    protected static function booted()
    {
        static::creating(function ($document) {
            if (!$document->status) {
                $document->status = self::STATUS_DRAFT;
            }
            if (!$document->version) {
                $document->version = 1;
            }
            if (!isset($document->is_public)) {
                $document->is_public = false;
            }
            if (!isset($document->is_featured)) {
                $document->is_featured = false;
            }
            if (!isset($document->is_required)) {
                $document->is_required = false;
            }
            if (!$document->download_count) {
                $document->download_count = 0;
            }
            if (!$document->view_count) {
                $document->view_count = 0;
            }
        });

        static::created(function ($document) {
            $document->clearCache();
        });

        static::updated(function ($document) {
            $document->clearCache();
        });

        static::deleted(function ($document) {
            $document->clearCache();
        });
    }
}