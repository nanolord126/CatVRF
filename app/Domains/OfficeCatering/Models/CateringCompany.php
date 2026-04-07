<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Models;

use App\Models\Traits\HasUuids;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CateringCompany
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
final class CateringCompany extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'catering_companies';
    protected $fillable = [
        'uuid', 'tenant_id', 'business_group_id', 'correlation_id',
        'name', 'owner_id', 'description', 'address', 'phone',
        'latitude', 'longitude', 'certification_number', 'is_verified',
        'commission_percent', 'min_order_amount', 'min_person_count',
        'max_person_count', 'delivery_zones', 'schedule', 'tags',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'commission_percent' => 'float',
        'latitude' => 'float', 'longitude' => 'float',
        'min_order_amount' => 'integer',
        'min_person_count' => 'integer',
        'max_person_count' => 'integer',
        'delivery_zones' => 'json',
        'schedule' => 'json',
        'tags' => 'json',
    ];

    public function orders() { return $this->hasMany(CateringOrder::class, 'catering_company_id'); }
    public function menus() { return $this->hasMany(CateringMenu::class, 'catering_company_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('catering_companies.tenant_id', tenant()->id));
    }
}
