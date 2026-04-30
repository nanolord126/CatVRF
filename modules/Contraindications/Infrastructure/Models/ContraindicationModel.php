<?php

declare(strict_types=1);

namespace Modules\Contraindications\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ContraindicationModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contraindications';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'pet_id',
        'name',
        'description',
        'severity',
        'scopes',
        'is_active',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.tenant_model'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(\Modules\VetGrooming\Infrastructure\Models\PetModel::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPet($query, int $petId)
    {
        return $query->where('pet_id', $petId);
    }

    public function scopeWithScope($query, string $scope)
    {
        return $query->whereJsonContains('scopes', $scope);
    }
}
