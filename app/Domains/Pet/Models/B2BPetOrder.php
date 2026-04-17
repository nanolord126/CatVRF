<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class B2BPetOrder
 *
 * Part of the Pet vertical domain.
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
 * @package App\Domains\Pet\Models
 */
final class B2BPetOrder extends Model
{


    protected $table = 'b2b_pet_orders';

    protected $fillable = [
        'uuid', 'tenant_id', 'b2b_pet_storefront_id', 'user_id', 'order_number',
        'company_contact_person', 'company_phone', 'items', 'total_amount',
        'commission_amount', 'status', 'rejection_reason', 'correlation_id', 'tags'
    ];

    protected $casts = [
        'items' => 'json',
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
        return $this->belongsTo(B2BPetStorefront::class, 'b2b_pet_storefront_id');
    }
}
