<?php declare(strict_types=1);

namespace App\Models\Dental;


use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class DentalTreatmentPlan extends Model
{
    public function __construct(
        private readonly Request $request,
    ) {}

    use HasFactory, SoftDeletes;

        protected $table = 'dental_treatment_plans';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'client_id',
            'dentist_id',
            'title',
            'steps',
            'estimated_budget',
            'status',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'steps' => 'json',
            'tags' => 'json',
            'estimated_budget' => 'integer',
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
         * Relations: Dentist in charge of the plan.
         */
        public function dentist(): BelongsTo
        {
            return $this->belongsTo(Dentist::class, 'dentist_id');
        }

        /**
         * Relations: Appointments linked to this treatment plan.
         */
        public function appointments(): HasMany
        {
            return $this->hasMany(DentalAppointment::class, 'treatment_plan_id');
        }

        /**
         * Calculate completion percentage derived from steps.
         */
        public function getCompletionRateAttribute(): float
        {
            $steps = $this->steps ?? [];
            if (empty($steps)) {
                return 0.0;
            }

            $completed = array_filter($steps, fn($step) => ($step['status'] ?? '') === 'completed');
            return count($completed) / count($steps) * 100;
        }

        /**
         * Health Privacy Guard (ФЗ-152).
         */
        public function anonymizeForAnalysis(): array
        {
            return [
                'plan_title' => $this->title,
                'steps_count' => count($this->steps ?? []),
                'estimated_budget' => $this->estimated_budget,
                'specialization' => $this->dentist?->specialization,
                'is_anonymized' => true,
            ];
        }

        /**
         * Add a professional recommendation step to the plan.
         */
        public function addStep(array $stepData): void
        {
            $steps = $this->steps ?? [];
            $steps[] = array_merge($stepData, [
                'id' => (string) Str::uuid(),
                'created_at' => now()->toIso8601String(),
                'status' => 'pending',
            ]);

            $this->update(['steps' => $steps]);
        }
}
