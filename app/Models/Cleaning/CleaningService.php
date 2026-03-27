<?php

declare(strict_types=1);

namespace App\Models\Cleaning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * CleaningService.
 * Definition of a specific cleaning task (e.g., Windows, Dry Cleaning).
 * Part of 2026 Canonical vertical implementation.
 */
final class CleaningService extends Model
{
    protected $table = 'cleaning_services';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'cleaning_company_id',
        'name',
        'description',
        'category', // standard, general, post_construction, window, dry_cleaning, office
        'price_base_cents',
        'unit', // sqm, hour, item
        'estimated_duration_minutes',
        'consumables_required',
        'is_active',
        'correlation_id',
    ];

    protected $casts = [
        'price_base_cents' => 'integer',
        'estimated_duration_minutes' => 'integer',
        'consumables_required' => 'json',
        'is_active' => 'boolean',
        'tenant_id' => 'integer',
        'cleaning_company_id' => 'integer',
    ];

    /**
     * Boot logic for metadata and tenant isolation.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (int) (tenant()->id ?? 0);
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Parent company provider.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(CleaningCompany::class, 'cleaning_company_id');
    }

    /**
     * Associated orders for this specific service.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(CleaningOrder::class);
    }

    /**
     * Formatting for price display (in base unit like RUB).
     */
    public function formattedPrice(): string
    {
        return number_format($this->price_base_cents / 100, 2, ',', ' ') . ' ₽ / ' . $this->unit;
    }

    /**
     * Validation check for availability.
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->company->is_verified;
    }
}
