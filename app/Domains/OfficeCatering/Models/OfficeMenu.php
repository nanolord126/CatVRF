<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Models;

use App\Models\Traits\HasUuids;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class OfficeMenu
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
final class OfficeMenu extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'office_menus';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'client_id', 'name', 'items', 'items_count',
        'price_per_serving', 'min_portions', 'active', 'tags',
    ];
    protected $casts = [
        'items'              => 'json',
        'items_count'        => 'int',
        'price_per_serving'  => 'int',
        'min_portions'       => 'int',
        'active'             => 'boolean',
        'tags'               => 'json',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CorporateClient::class, 'client_id');
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
