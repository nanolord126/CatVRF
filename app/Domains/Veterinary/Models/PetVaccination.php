<?php

declare(strict_types=1);

namespace App\Domains\Veterinary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Pet Vaccination Model (CatVRF 2026)
 * Журнал прививок питомца (Ветеринарный паспорт)
 */
final class PetVaccination extends Model
{
    use SoftDeletes;

    protected $table = 'pet_vaccinations';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'pet_id',
        'veterinarian_id',
        'vaccine_name',
        'serial_number',
        'vaccinated_at',
        'expires_at',
        'certificate_url',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'vaccinated_at' => 'date',
        'expires_at' => 'date',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (PetVaccination $model) {
            $model->uuid = (string) Str::uuid();
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
        
        static::addGlobalScope('tenant_id', function ($builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(Veterinarian::class);
    }
}
