<?php declare(strict_types=1);

namespace App\Domains\Education\Social\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SocialMedia extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
