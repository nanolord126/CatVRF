<?php declare(strict_types=1);

namespace App\Domains\Photography\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class B2BPhotoOrder
 *
 * Part of the Photography vertical domain.
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
 * @package App\Domains\Photography\Models
 */
final class B2BPhotoOrder extends Model
{

    use HasFactory;
    use SoftDeletes;

    protected $table = 'b2b_photo_orders';

    protected $fillable = [
        'uuid', 'tenant_id', 'b2b_photo_storefront_id', 'photographer_id', 'order_number',
        'company_contact_person', 'company_phone', 'datetime_start', 'duration_hours',
        'total_amount', 'commission_amount', 'status', 'notes', 'correlation_id', 'tags'
    ];

    protected $casts = [
        'datetime_start' => 'datetime',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant() && tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(B2BPhotoStorefront::class, 'b2b_photo_storefront_id');
    }

    public function photographer(): BelongsTo
    {
        return $this->belongsTo(Photographer::class, 'photographer_id');
    }
}
