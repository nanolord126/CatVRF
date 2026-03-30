<?php declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CollectibleCertificate extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'collectible_certificates';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'item_id',
            'certificate_number',
            'issuer',
            'issued_at',
            'report_data',
            'correlation_id',
        ];

        protected $casts = [
            'issued_at' => 'date',
            'report_data' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (CollectibleCertificate $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (tenant()->id ?? 1));
            });
        }

        /**
         * Item linked to this certificate.
         */
        public function item(): BelongsTo
        {
            return $this->belongsTo(CollectibleItem::class, 'item_id');
        }
}
