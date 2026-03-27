<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * РЕЖИМ ЛЮТЫЙ 2026: PET CLINIC MODEL
 * 
 * Модель ветеринарной клиники, груминг-салона или зооцентра.
 * Scoping: tenant_id, business_group_id.
 * Канон: 60+ строк, UUID, correlation_id, JSONB tags.
 */
final class PetClinic extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pet_clinics';

    /**
     * Поля, доступные для массового заполнения.
     */
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

    /**
     * Приведение типов.
     */
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

    /**
     * Скрытые поля.
     */
    protected $hidden = ['correlation_id'];

    /**
     * Инициализация модели.
     */
    protected static function booted(): void
    {
        // Global scope для изоляции тенантов и филиалов
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        // Автогенерация UUID и correlation_id
        static::creating(function (PetClinic $model) {
            $model->uuid = $model->uuid ?? (string) \Illuminate\Support\Str::uuid();
            $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
            
            if (function_exists('tenant') && tenant()) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }

    /**
     * Персонал клиники (ветеринары/специалисты).
     */
    public function vets(): HasMany
    {
        return $this->hasMany(Veterinarian::class, 'clinic_id');
    }

    /**
     * Услуги, предоставляемые клиникой.
     */
    public function services(): HasMany
    {
        return $this->hasMany(PetService::class, 'clinic_id');
    }

    /**
     * Записи на прием в эту клинику.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(PetAppointment::class, 'clinic_id');
    }

    /**
     * Зоотовары в этой клинике/магазине.
     */
    public function products(): HasMany
    {
        return $this->hasMany(PetProduct::class, 'clinic_id');
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


    public function reviews(): HasMany
    {
        return $this->hasMany(PetReview::class, 'clinic_id');
    }
}
