<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Flowers\Domain\Entities\Florist as FloristEntity;
use Modules\Media\Domain\Traits\HasMediaTrait;

final class FloristModel extends Model
{
    use SoftDeletes;
    use HasMediaTrait;

    protected $table = 'flowers_florists';

    protected $fillable = [
        'venue_id',
        'user_id',
        'tenant_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'working_hours',
        'hourly_rate',
        'specialization',
        'skills',
        'orders_completed',
        'average_rating',
        'total_rating_count',
        'is_available',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'hourly_rate' => 'decimal:2',
        'orders_completed' => 'integer',
        'average_rating' => 'decimal:2',
        'total_rating_count' => 'integer',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(OrderModel::class, 'florist_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeTopRated($query)
    {
        return $query->where('average_rating', '>=', 4.5)
            ->where('total_rating_count', '>=', 10);
    }

    public function scopeExperienced($query)
    {
        return $query->where('orders_completed', '>=', 50);
    }

    public function toDomain(): FloristEntity
    {
        return new FloristEntity(
            id: $this->id,
            venueId: $this->venue_id,
            userId: $this->user_id,
            tenantId: $this->tenant_id,
            firstName: $this->first_name,
            lastName: $this->last_name,
            phone: $this->phone,
            email: $this->email,
            workingHours: $this->working_hours,
            hourlyRate: $this->hourly_rate ? (float) $this->hourly_rate : null,
            specialization: $this->specialization,
            skills: $this->skills,
            ordersCompleted: $this->orders_completed,
            averageRating: (float) $this->average_rating,
            totalRatingCount: $this->total_rating_count,
            isAvailable: $this->is_available,
            isActive: $this->is_active,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? \Carbon\CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(FloristEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'venue_id' => $entity->venueId,
            'user_id' => $entity->userId,
            'tenant_id' => $entity->tenantId,
            'first_name' => $entity->firstName,
            'last_name' => $entity->lastName,
            'phone' => $entity->phone,
            'email' => $entity->email,
            'working_hours' => $entity->workingHours,
            'hourly_rate' => $entity->hourlyRate,
            'specialization' => $entity->specialization,
            'skills' => $entity->skills,
            'orders_completed' => $entity->ordersCompleted,
            'average_rating' => $entity->averageRating,
            'total_rating_count' => $entity->totalRatingCount,
            'is_available' => $entity->isAvailable,
            'is_active' => $entity->isActive,
            'metadata' => $entity->metadata,
        ]);
    }
}
