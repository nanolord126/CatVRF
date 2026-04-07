<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string              $id
 * @property int                 $tenant_id
 * @property string              $property_id
 * @property int                 $client_id
 * @property string              $agent_id
 * @property \DateTimeInterface  $scheduled_at
 * @property string              $status
 * @property string|null         $client_name
 * @property string|null         $client_phone
 * @property string|null         $notes
 * @property string|null         $cancellation_reason
 * @property string|null         $correlation_id
 * @property array|null          $tags
 */
final class ViewingAppointmentModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'real_estate_viewings';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'id',
        'tenant_id',
        'property_id',
        'client_id',
        'agent_id',
        'scheduled_at',
        'status',
        'client_name',
        'client_phone',
        'notes',
        'cancellation_reason',
        'correlation_id',
        'tags',
    ];

    protected $hidden = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'tags'         => 'array',
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
