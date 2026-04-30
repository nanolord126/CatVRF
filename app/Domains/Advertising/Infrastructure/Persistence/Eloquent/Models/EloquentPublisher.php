<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models;

use App\Domains\Advertising\Domain\Entities\Publisher as PublisherEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent Publisher Model
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
class EloquentPublisher extends Model
{
    use SoftDeletes;

    protected $table = 'publishers';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'website_url',
        'status',
        'commission_rate',
        'payout_threshold',
        'api_key',
        'webhook_url',
        'integration_type',
        'verified_at',
        'last_payout_at',
        'correlation_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'commission_rate' => 'decimal:4',
        'payout_threshold' => 'integer',
        'verified_at' => 'datetime',
        'last_payout_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function toDomain(): PublisherEntity
    {
        return new PublisherEntity(
            id: $this->id,
            uuid: $this->uuid,
            tenant_id: $this->tenant_id,
            name: $this->name,
            website_url: $this->website_url,
            status: $this->status,
            commission_rate: (float) $this->commission_rate,
            payout_threshold: $this->payout_threshold,
            api_key: $this->api_key,
            webhook_url: $this->webhook_url,
            integration_type: $this->integration_type,
            verified_at: $this->verified_at,
            last_payout_at: $this->last_payout_at,
            correlation_id: $this->correlation_id,
        );
    }

    public static function fromDomain(PublisherEntity $entity): self
    {
        return new self([
            'uuid' => $entity->uuid,
            'tenant_id' => $entity->tenant_id,
            'name' => $entity->name,
            'website_url' => $entity->website_url,
            'status' => $entity->status,
            'commission_rate' => $entity->commission_rate,
            'payout_threshold' => $entity->payout_threshold,
            'api_key' => $entity->api_key,
            'webhook_url' => $entity->webhook_url,
            'integration_type' => $entity->integration_type,
            'verified_at' => $entity->verified_at,
            'last_payout_at' => $entity->last_payout_at,
            'correlation_id' => $entity->correlation_id,
        ]);
    }
}
