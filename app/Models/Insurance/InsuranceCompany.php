<?php declare(strict_types=1);

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InsuranceCompany extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'insurance_companies';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'inn',
            'license_number',
            'rating',
            'contacts',
            'settings',
            'status',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'contacts' => 'json',
            'settings' => 'json',
            'tags' => 'json',
            'rating' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        protected $hidden = [
            'settings',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (auth()->check() && empty($model->tenant_id)) {
                    $model->tenant_id = auth()->user()->tenant_id;
                }
            });

            // Global scope for tenant isolation
            static::addGlobalScope('tenant', function ($builder) {
                if (auth()->check()) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                }
            });
        }

        /**
         * Policies issued by this company.
         */
        public function policies(): HasMany
        {
            return $this->hasMany(InsurancePolicy::class, 'company_id');
        }

        /**
         * Reviews for this company.
         */
        public function reviews(): HasMany
        {
            return $this->hasMany(InsuranceReview::class, 'company_id');
        }

        /**
         * Claims related to this company through policies.
         */
        public function claims(): HasMany
        {
            return $this->hasManyThrough(
                InsuranceClaim::class,
                InsurancePolicy::class,
                'company_id',
                'policy_id',
                'id',
                'id'
            );
        }
}
