<?php

namespace App\Models;

use App\Enums\DocumentProcessingStatus;
use App\Enums\DocumentType;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $documentable_type
 * @property int $documentable_id
 * @property DocumentType $document_type
 * @property string $title
 * @property string|null $description
 * @property string|null $disk
 * @property string|null $path
 * @property string|null $file_path
 * @property string|null $original_filename
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property string|null $checksum
 * @property int $version
 * @property bool $is_current
 * @property bool $approved
 * @property DocumentProcessingStatus $processing_status
 * @property float|null $processing_confidence
 * @property array<string, mixed>|null $processing_result
 * @property array<string, mixed>|null $processing_error
 * @property Carbon|null $processed_at
 * @property int|null $uploaded_by
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $approver
 * @property-read Model $documentable
 * @property-read User|null $uploader
 *
 * @method static \Database\Factories\DocumentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDocumentableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDocumentableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereIsCurrent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereOriginalFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUploadedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'documentable_type',
    'documentable_id',
    'document_type',
    'title',
    'description',
    'disk',
    'path',
    'file_path',
    'original_filename',
    'mime_type',
    'file_size',
    'checksum',
    'version',
    'is_current',
    'approved',
    'processing_status',
    'processing_confidence',
    'processing_result',
    'processing_error',
    'processed_at',
    'uploaded_by',
    'approved_by',
    'approved_at',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'processing_status' => DocumentProcessingStatus::class,
            'processing_confidence' => 'float',
            'processing_result' => 'array',
            'processing_error' => 'array',
            'file_size' => 'integer',
            'version' => 'integer',
            'is_current' => 'boolean',
            'approved' => 'boolean',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
