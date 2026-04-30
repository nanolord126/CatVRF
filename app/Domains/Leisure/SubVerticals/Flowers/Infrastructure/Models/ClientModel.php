<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Flowers\Domain\Entities\Client as ClientEntity;

final class ClientModel extends Model
{
    use SoftDeletes;

    protected $table = 'flowers_clients';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'birth_date',
        'preferences',
        'notes',
        'total_orders',
        'total_spent',
        'loyalty_points',
        'loyalty_tier',
        'last_order_date',
        'is_subscribed',
        'metadata',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'total_orders' => 'integer',
        'total_spent' => 'decimal:2',
        'loyalty_points' => 'integer',
        'loyalty_tier' => 'string',
        'last_order_date' => 'datetime',
        'is_subscribed' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(OrderModel::class, 'client_id');
    }

    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }

    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    public function scopeVip($query)
    {
        return $query->whereIn('loyalty_tier', ['gold', 'platinum']);
    }

    public function scopeLoyal($query)
    {
        return $query->where('total_orders', '>=', 3);
    }

    public function scopeWithBirthdaySoon($query, int $days = 7)
    {
        return $query->whereRaw('DATE_ADD(birth_date, INTERVAL YEAR(CURDATE())-YEAR(birth_date) YEAR) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)', [$days]);
    }

    public function toDomain(): ClientEntity
    {
        return new ClientEntity(
            id: $this->id,
            userId: $this->user_id,
            tenantId: $this->tenant_id,
            firstName: $this->first_name,
            lastName: $this->last_name,
            phone: $this->phone,
            email: $this->email,
            birthDate: $this->birth_date ? \Carbon\CarbonImmutable::parse($this->birth_date) : null,
            preferences: $this->preferences,
            notes: $this->notes,
            totalOrders: $this->total_orders,
            totalSpent: (float) $this->total_spent,
            loyaltyPoints: $this->loyalty_points,
            loyaltyTier: $this->loyalty_tier,
            lastOrderDate: $this->last_order_date ? \Carbon\CarbonImmutable::parse($this->last_order_date) : null,
            isSubscribed: $this->is_subscribed,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? \Carbon\CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(ClientEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'user_id' => $entity->userId,
            'tenant_id' => $entity->tenantId,
            'first_name' => $entity->firstName,
            'last_name' => $entity->lastName,
            'phone' => $entity->phone,
            'email' => $entity->email,
            'birth_date' => $entity->birthDate?->format('Y-m-d'),
            'preferences' => $entity->preferences,
            'notes' => $entity->notes,
            'total_orders' => $entity->totalOrders,
            'total_spent' => $entity->totalSpent,
            'loyalty_points' => $entity->loyaltyPoints,
            'loyalty_tier' => $entity->loyaltyTier,
            'last_order_date' => $entity->lastOrderDate,
            'is_subscribed' => $entity->isSubscribed,
            'metadata' => $entity->metadata,
        ]);
    }
}
