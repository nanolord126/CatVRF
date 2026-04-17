<?php declare(strict_types=1);

/**
 * CateringMenu — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cateringmenu
 */


namespace App\Domains\OfficeCatering\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CateringMenu
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
final class CateringMenu extends Model
{

    protected $table = 'catering_menus';
    protected $fillable = ['uuid', 'tenant_id', 'catering_company_id', 'correlation_id', 'name', 'description', 'price_kopecks', 'items_json', 'for_person_count', 'is_active', 'available_days', 'tags'];

    protected $casts = ['price_kopecks' => 'integer', 'items_json' => 'json', 'for_person_count' => 'integer', 'is_active' => 'boolean', 'available_days' => 'json', 'tags' => 'json'];

    /**
     * Handle company operation.
     *
     * @throws \DomainException
     */
    public function company() { return $this->belongsTo(CateringCompany::class, 'catering_company_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('catering_menus.tenant_id', tenant()->id));
    }
}
