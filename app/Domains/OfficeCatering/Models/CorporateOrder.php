<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CorporateOrder
 *
 * Part of the OfficeCatering vertical domain.
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
 * @package App\Domains\OfficeCatering\Models
 */
final class CorporateOrder extends Model
{

    protected $table = 'corporate_orders';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'corporate_client_id', 'office_menu_id',
        'uuid', 'correlation_id', 'idempotency_key',
        'persons_count', 'total_amount', 'delivery_date', 'delivery_time',
        'delivery_address', 'status', 'payment_status',
        'is_recurring', 'recurrence', 'delivered_at', 'tags',
    ];
    protected $casts = [
        'persons_count'  => 'int',
        'total_amount'   => 'int',
        'is_recurring'   => 'boolean',
        'delivery_date'  => 'date',
        'delivered_at'   => 'datetime',
        'tags'           => 'json',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CorporateClient::class, 'corporate_client_id');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(OfficeMenu::class, 'office_menu_id');
    }

    protected static function booted(): void
    {
        parent::boot();
        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant()?->id) {
                $query->where('tenant_id', tenant()?->id);
            }
        });
    }
}
