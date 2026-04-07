<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PostMedia extends Model
{
    use HasFactory;

    protected $table = 'post_media';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'post_id',
            'tenant_id',
            'type',
            'url',
            'thumbnail_url',
            'mime_type',
            'size_bytes',
            'width',
            'height',
            'duration_seconds',
            'alt_text',
            'sort_order',
            'correlation_id',
        ];

        protected $casts = [
            'size_bytes'       => 'integer',
            'width'            => 'integer',
            'height'           => 'integer',
            'duration_seconds' => 'integer',
            'sort_order'       => 'integer',
        ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


        public function post(): BelongsTo
        {
            return $this->belongsTo(Post::class, 'post_id');
        }

        public function isImage(): bool
        {
            return $this->type === 'image';
        }

        public function isVideo(): bool
        {
            return in_array($this->type, ['video', 'shorts'], true);
        }

        public function isShorts(): bool
        {
            return $this->type === 'shorts';
        }
}
