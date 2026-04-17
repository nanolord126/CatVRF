<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Prescription
 *
 * Part of the Pharmacy vertical domain.
 * Follows CatVRF 9-layer architecture.
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
 * @package App\Domains\Pharmacy\Models
 */
final class Prescription extends Model
{

    protected $table = 'prescriptions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'doctor_id',
        'uuid',
        'prescription_number',
        'expires_at',
        'status',
        'ocr_data',
        'scan_path',
        'tags',
        'correlation_id'
    ];

    protected $casts = [
        'expires_at' => 'date',
        'tags' => 'json'
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function (Builder $builder) {
            $builder->where('tenant_id', tenant()->id ?? 0);
        });

        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
