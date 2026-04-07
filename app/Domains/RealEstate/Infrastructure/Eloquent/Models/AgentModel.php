<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string      $id
 * @property int         $tenant_id
 * @property int         $user_id
 * @property string      $full_name
 * @property string      $license_number
 * @property float       $rating
 * @property int         $deals_count
 * @property bool        $is_active
 * @property string|null $correlation_id
 * @property array|null  $tags
 */
final class AgentModel extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'real_estate_agents';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'id',
        'tenant_id',
        'user_id',
        'full_name',
        'license_number',
        'rating',
        'deals_count',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'rating'      => 'float',
        'deals_count' => 'integer',
        'is_active'   => 'boolean',
        'tags'        => 'array',
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(PropertyModel::class, 'agent_id', 'id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($builder): void {
            if (function_exists('tenant') && tenant() !== null) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
