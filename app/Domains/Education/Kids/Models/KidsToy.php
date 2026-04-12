<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsToy extends Model
{
    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'kids_toys';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'product_id',
            'toy_type',
            'material_type',
            'has_batteries',
            'battery_type',
            'educational_goals',
            'safety_certificates',
            'brand_name',
            'correlation_id',
        ];

        protected $casts = [
            'has_batteries' => 'boolean',
            'educational_goals' => 'json', // logic, motor_skills, patience, social
            'safety_certificates' => 'json', // ISO, GOST, EAC
        ];

        /**
         * Boot the model with tenant and correlation scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (string) (tenant()->id ?? 'system');
                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Product relationship.
         */
        public function product(): BelongsTo
        {
            return $this->belongsTo(KidsProduct::class, 'product_id');
        }

        /**
         * Filter by educational goals.
         */
        public function scopeForMotorSkills(Builder $query): Builder
        {
            return $query->whereJsonContains('educational_goals', 'motor_skills');
        }

        /**
         * Filter by safety compliance.
         */
        public function scopeEacCompliant(Builder $query): Builder
        {
            return $query->whereJsonContains('safety_certificates', 'EAC');
        }

        /**
         * Check if toy is dangerous (no certifications).
         */
        public function isUnverified(): bool
        {
            return empty($this->safety_certificates);
        }

        /**
         * Get educational focus labels.
         */
        public function getEducationLabels(): array
        {
            return collect($this->educational_goals)->map(fn($g) => __('kids.education.' . $g))->toArray();
        }
}
