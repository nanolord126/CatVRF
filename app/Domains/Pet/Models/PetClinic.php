<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PetClinic extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pet_clinics';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'type', // clinic, grooming, shop, hotel
        'address',
        'geo_point',
        'schedule_json',
        'rating',
        'review_count',
        'is_verified',
        'has_emergency',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'geo_point' => 'json',
        'schedule_json' => 'json',
        'tags' => 'json',
        'rating' => 'float',
        'review_count' => 'integer',
        'is_verified' => 'boolean',
        'has_emergency' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = ['correlation_id'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (PetClinic $model) {
            $model->uuid = $model->uuid ?? (string) \Illuminate\Support\Str::uuid();
            $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());

            if (function_exists('tenant') && tenant()) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }

    public function vets(): HasMany
    {
        return $this->hasMany(Veterinarian::class, 'clinic_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(PetService::class, 'clinic_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(PetAppointment::class, 'clinic_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(PetProduct::class, 'clinic_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PetReview::class, 'clinic_id');
    }

    /**
     * Проверка: является ли объект только груминг-салоном.
     */
    public function isGroomingOnly(): bool
    {
        return $this->type === 'grooming';
    }

    /**
     * Проверка: работает ли клиника в режиме 24/7.
     */
    public function isEmergencyReady(): bool
    {
        return $this->has_emergency;
    }

    /**
     * Получить средний рейтинг (для UI).
     */
    public function getFormattedRating(): string
    {
        return number_format($this->rating, 1) . ' ★';
    }

    /**
     * Проверка верификации клиники.
     */
    public function isVerifiedClinic(): bool
    {
        return $this->is_verified;
    }
}
