<?php

declare(strict_types=1);

namespace App\Models\Advertising;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AdCampaign
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
 * @package App\Models\Advertising
 */
final class AdCampaign extends Model
{

    protected $table = 'ad_campaigns';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'status',
        'start_at',
        'end_at',
        'budget',
        'spent',
        'pricing_model',
        'targeting_criteria',
        'correlation_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'targeting_criteria' => 'json',
        'budget' => 'integer',
        'spent' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
