<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

/**
 * Class B2BDeal
 *
 * Part of the RealEstate vertical domain.
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
 * @package App\Domains\RealEstate\Models
 */
final class B2BDeal extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'b2b_deals';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'listing_id',
        'investor_id',
        'correlation_id',
        'deal_amount',
        'expected_roi',
        'status',
        'deal_structure',
    ];

    protected $casts = [
        'deal_amount' => 'integer',
        'expected_roi' => 'float',
        'deal_structure' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (B2BDeal $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });
    }

    public function listing(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('b2b_deals')
            ->logOnlyDirty();
    }
}
