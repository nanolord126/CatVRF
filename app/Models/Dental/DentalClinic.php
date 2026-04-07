<?php declare(strict_types=1);

namespace App\Models\Dental;


use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class DentalClinic extends Model
{
    public function __construct(
        private readonly Request $request,
    ) {}

    use HasFactory, SoftDeletes;

        protected $table = 'dental_clinics';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'license_number',
            'address',
            'schedule',
            'rating',
            'is_premium',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'schedule' => 'json',
            'tags' => 'json',
            'is_premium' => 'boolean',
            'rating' => 'integer',
            'tenant_id' => 'integer',
        ];

        /**
         * Boot logic for automatic UUID and tenant scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) Str::uuid());

                // Auto-assign tenant if authenticated
                if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Relations: Dentists working in the clinic.
         */
        public function dentists(): HasMany
        {
            return $this->hasMany(Dentist::class, 'clinic_id');
        }

        /**
         * Relations: Services offered by the clinic.
         */
        public function services(): HasMany
        {
            return $this->hasMany(DentalService::class, 'clinic_id');
        }

        /**
         * Relations: Consumables owned by the clinic.
         */
        public function consumables(): HasMany
        {
            return $this->hasMany(DentalConsumable::class, 'clinic_id');
        }

        /**
         * Relations: Appointments scheduled at the clinic.
         */
        public function appointments(): HasMany
        {
            return $this->hasMany(DentalAppointment::class, 'clinic_id');
        }

        /**
         * Check if the clinic is currently open based on schedule.
         */
        public function isOpenNow(): bool
        {
            // Complex logic for checking business hours from JSON schedule
            $now = now();
            $dayName = strtolower($now->format('l'));
            $schedule = $this->schedule[$dayName] ?? null;

            if (!$schedule || ($schedule['is_closed'] ?? false)) {
                return false;
            }

            $currentTime = $now->format('H:i');
            return $currentTime >= ($schedule['open'] ?? '09:00') && $currentTime <= ($schedule['close'] ?? '21:00');
        }
}
