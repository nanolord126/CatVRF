<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\OfficeCatering\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class CorporateClient
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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class CorporateClient extends Model
{
    protected $table = 'corporate_clients';

    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'company_name', 'inn', 'kpp', 'ogrn', 'address',
        'contact_person', 'phone', 'email', 'logo_url',
        'employee_count', 'status', 'tags',
    ];

    protected $casts = [
        'employee_count' => 'int',
        'tags'           => 'json',
    ];

    protected $hidden = ['inn', 'kpp', 'ogrn'];

    protected static function booted(): void
    {
        parent::boot();
        self::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant()?->id) {
                $query->where('tenant_id', tenant()?->id);
            }
        });
    }
}
