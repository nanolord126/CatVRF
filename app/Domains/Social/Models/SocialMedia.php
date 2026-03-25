<?php
declare(strict_types=1);

namespace App\Domains\Social\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property string $file_path
 * @property string $mime_type
 */
final class SocialMedia extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'social_media';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'mediable_type',
        'mediable_id',
        'file_path',
        'mime_type',
        'processing_status',
        'meta',
        'correlation_id',
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });
    }

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
