<?php
declare(strict_types=1);

/**
 * GardenProfessional — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/gardenprofessional
 */


namespace App\Domains\Gardening\GardenServices\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GardenProfessional extends Model
{
    use HasFactory;


    protected $table = 'garden_professionals';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'correlation_id',
        'name',
        'services',
        'price_kopecks_per_hour',
        'rating',
        'is_verified',
        'tags',
    ];

    protected $casts = [
        'services' => 'json',
        'price_kopecks_per_hour' => 'integer',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('garden_professionals.tenant_id', tenant()->id));

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
