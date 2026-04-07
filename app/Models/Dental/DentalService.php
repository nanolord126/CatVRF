<?php declare(strict_types=1);

namespace App\Models\Dental;


use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class DentalService extends Model
{
    public function __construct(
        private readonly Request $request,
    ) {}

    use HasFactory, SoftDeletes;

        protected $table = 'dental_services';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'name',
            'description',
            'base_price',
            'duration_minutes',
            'consumables_required',
            'category',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'consumables_required' => 'json',
            'tags' => 'json',
            'base_price' => 'integer',
            'duration_minutes' => 'integer',
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
         * Relations: Clinic offering the service.
         */
        public function clinic(): BelongsTo
        {
            return $this->belongsTo(DentalClinic::class, 'clinic_id');
        }

        /**
         * Relations: Appointments for this service.
         */
        public function appointments(): HasMany
        {
            return $this->hasMany(DentalAppointment::class, 'service_id');
        }

        /**
         * Get price in rubles (for display).
         */
        public function getPriceInRubAttribute(): float
        {
            return $this->base_price / 100;
        }

        /**
         * Get duration as a string.
         */
        public function getDurationStringAttribute(): string
        {
            $hours = floor($this->duration_minutes / 60);
            $minutes = $this->duration_minutes % 60;

            if ($hours > 0) {
                return "{$hours}h {$minutes}min";
            }

            return "{$minutes}min";
        }

        /**
         * Check if a service requires prepayment.
         * Complex works (orthodontics, surgery) usually do.
         */
        public function requiresPrepayment(): bool
        {
            $highValueCategories = ['Orthodontics', 'Surgery', 'Implantation'];
            return in_array($this->category, $highValueCategories) || $this->base_price > 500000; // > 5000 rub
        }
}
