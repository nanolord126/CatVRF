<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string              $id
 * @property int                 $tenant_id
 * @property string              $property_id
 * @property string              $agent_id
 * @property int                 $client_id
 * @property string              $type
 * @property int                 $price_kopecks
 * @property int                 $commission_kopecks
 * @property string              $status
 * @property int|null            $lease_duration_months
 * @property string|null         $document_url
 * @property \DateTimeInterface|null $signed_at
 * @property \DateTimeInterface|null $terminated_at
 * @property string|null         $correlation_id
 * @property array|null          $tags
 */
final class ContractModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'real_estate_contracts';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'id',
        'tenant_id',
        'property_id',
        'agent_id',
        'client_id',
        'type',
        'price_kopecks',
        'commission_kopecks',
        'status',
        'lease_duration_months',
        'document_url',
        'signed_at',
        'terminated_at',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'price_kopecks'        => 'integer',
        'commission_kopecks'   => 'integer',
        'lease_duration_months'=> 'integer',
        'signed_at'            => 'datetime',
        'terminated_at'        => 'datetime',
        'tags'                 => 'array',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(PropertyModel::class, 'property_id', 'id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AgentModel::class, 'agent_id', 'id');
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
