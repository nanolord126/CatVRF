<?php declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BContract extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'hotel_b2b_contracts';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'hotel_id',
            'name',
            'discount_percent',
            'is_active',
            'is_valid',
            'contract_data',
            'correlation_id',
        ];

        protected $casts = [
            'discount_percent' => 'integer',
            'is_active' => 'boolean',
            'is_valid' => 'boolean',
            'contract_data' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) tenant('id');
                $model->business_group_id = $model->business_group_id ?? 1;
            });

            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', (int) tenant('id'));
            });
        }

        public function hotel(): BelongsTo
        {
            return $this->belongsTo(Hotel::class);
        }

        /**
         * Проверить валидность контракта.
         */
        public function isValid(): bool
        {
            return $this->is_active && $this->is_valid;
        }
}
