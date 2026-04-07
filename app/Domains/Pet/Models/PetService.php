<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class PetService extends Model
{
    use HasFactory;

    protected $table = 'pet_services';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'clinic_id',
        'name',
        'category', // surgery, grooming, diagnostics, vaccination
        'duration_minutes',
        'price', // Цена в копейках
        'consumables_json',
        'requires_vaccination',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'consumables_json' => 'json',
        'tags' => 'json',
        'duration_minutes' => 'integer',
        'price' => 'integer',
        'requires_vaccination' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (PetService $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) Str::uuid());

            if (function_exists('tenant') && tenant()) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(PetClinic::class, 'clinic_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(PetAppointment::class, 'service_id');
    }

    public function isVaccinationRequired(): bool
    {
        return $this->requires_vaccination;
    }

    public function getPriceInRubles(): float
    {
        return (float) ($this->price / 100);
    }
}
