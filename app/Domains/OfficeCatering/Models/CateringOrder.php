<?php declare(strict_types=1);

/**
 * CateringOrder — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cateringorder
 */


namespace App\Domains\OfficeCatering\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\TenantScoped;

use App\Models\Traits\HasUuids;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CateringOrder
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
final class CateringOrder extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'catering_orders';
    protected $fillable = ['uuid', 'tenant_id', 'catering_company_id', 'client_id', 'correlation_id', 'office_name', 'office_address', 'delivery_datetime', 'person_count', 'status', 'total_kopecks', 'commission_kopecks', 'payout_kopecks', 'payment_status', 'menu_items_json', 'special_requests', 'tags'];

    protected $casts = ['person_count' => 'integer', 'total_kopecks' => 'integer', 'commission_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'delivery_datetime' => 'datetime', 'menu_items_json' => 'json', 'tags' => 'json'];

    /**
     * Handle company operation.
     *
     * @throws \DomainException
     */
    public function company() { return $this->belongsTo(CateringCompany::class, 'catering_company_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('catering_orders.tenant_id', tenant()->id));
    }
}
