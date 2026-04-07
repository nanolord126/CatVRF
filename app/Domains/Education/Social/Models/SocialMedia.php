<?php declare(strict_types=1);

namespace App\Domains\Education\Social\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SocialMedia extends Model
{
    use HasFactory;

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
         * @throws \RuntimeException
         */
        public function mediable(): MorphTo
        {
            return $this->morphTo();
        }
}
