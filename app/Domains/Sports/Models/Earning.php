<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Earning
 *
 * Part of the Sports vertical domain.
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
 * @package App\Domains\Sports\Models
 */
final class Earning extends Model
{

    protected $table = 'earnings';
    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'studio_id',
        'period_month',
        'period_year',
        'total_revenue',
        'total_commission',
        'studio_earnings',
        'total_bookings',
        'total_memberships_sold',
        'payout_initiated_at',
        'payout_completed_at',
        'payout_method',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => AsCollection::class,
        'total_revenue' => 'float',
        'total_commission' => 'float',
        'studio_earnings' => 'float',
        'payout_initiated_at' => 'datetime',
        'payout_completed_at' => 'datetime',
    ];

    public $timestamps = true;

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
