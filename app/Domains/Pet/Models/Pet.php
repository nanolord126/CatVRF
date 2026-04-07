<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Pet extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pets';

    /**
     * Поля, доступные для массового заполнения.
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'owner_id',
        'name',
        'species', // собака, кошка, птица, рептилия
        'breed',
        'birth_date',
        'gender', // male, female
        'weight_kg',
        'is_neutered',
        'microchip_id',
        'vaccination_history',
        'allergy_data',
        'correlation_id',
        'tags',
    ];

    /**
     * Приведение типов.
     */
    protected $casts = [
        'birth_date' => 'date',
        'vaccination_history' => 'json',
        'allergy_data' => 'json',
        'tags' => 'json',
        'weight_kg' => 'float',
        'is_neutered' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Инициализация модели.
     */
    protected static function booted(): void
    {
        // Global scope для изоляции тенантов
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        // Автогенерация UUID и correlation_id
        static::creating(function (Pet $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) Str::uuid());

            if (function_exists('tenant') && tenant()) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }

    /**
     * Записи на прием этого питомца.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(PetAppointment::class, 'pet_id');
    }

    /**
     * Расчет возраста питомца в годах.
     */
    public function getAgeYears(): int
    {
        if (!$this->birth_date) {
            return 0;
        }
        return Carbon::parse($this->birth_date)->age;
    }

    /**
     * Расчет возраста питомца в месяцах.
     */
    public function getAgeMonths(): int
    {
        if (!$this->birth_date) {
            return 0;
        }
        return (int) Carbon::parse($this->birth_date)->diffInMonths(Carbon::now());
    }

    /**
     * Проверка: является ли питомец молодым (менее 1 года).
     */
    public function isPuppyOrKitten(): bool
    {
        return $this->getAgeYears() < 1;
    }

    /**
     * Проверка: является ли питомец пожилым (более 10 лет).
     */
    public function isSenior(): bool
    {
        return $this->getAgeYears() >= 10;
    }

    /**
     * Проверка статуса вакцинации (пример логики).
     */
    public function needsVaccination(): bool
    {
        $history = $this->vaccination_history ?? [];
        if (empty($history)) {
            return true;
        }

        $latest = collect($history)->sortByDesc('date')->first();
        if (!$latest) return true;

        $vaccDate = Carbon::parse($latest['date']);
        return $vaccDate->diffInMonths(now()) >= 12;
    }

    /**
     * Получить полный заголовок питомца (имя + порода).
     */
    public function getFullPetTitle(): string
    {
        $species = match($this->species) {
            'cat' => 'Кошка',
            default => $this->species
        };

        return sprintf('%s (%s, %s)', $this->name, $species, $this->breed ?? 'Метис');
    }

    /**
     * Проверка аллергий.
     */
    public function hasAllergy(string $ingredient): bool
    {
        $allergies = $this->allergy_data ?? [];
        return in_array($ingredient, $allergies);
    }
}
