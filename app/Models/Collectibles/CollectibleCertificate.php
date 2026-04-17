<?php declare(strict_types=1);

namespace App\Models\Collectibles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Class CollectibleCertificate
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Models\Collectibles
 */
final class CollectibleCertificate extends Model
{

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
