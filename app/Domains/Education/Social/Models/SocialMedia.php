<?php declare(strict_types=1);

namespace App\Domains\Education\Social\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

final class SocialMedia extends Model
{
    use HasFactory, TenantScoped;

    use HasFactory, TenantScoped;

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
