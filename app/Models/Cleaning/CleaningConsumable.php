<?php declare(strict_types=1);

namespace App\Models\Cleaning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CleaningConsumable extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'cleaning_consumables';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'cleaning_company_id',
            'name',
            'sku',
            'stock_quantity',
            'min_threshold',
            'unit', // pcs, ml, gram, pack
            'safety_data',
            'correlation_id',
        ];

        protected $casts = [
            'stock_quantity' => 'integer',
            'min_threshold' => 'integer',
            'safety_data' => 'json',
            'tenant_id' => 'integer',
            'cleaning_company_id' => 'integer',
        ];

        /**
         * Boot logic for metadata and tenant isolation.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) (tenant()->id ?? 0);
            });

            static::addGlobalScope('tenant', function ($query) {
                if (tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Company that owns this inventory item.
         */
        public function company(): BelongsTo
        {
            return $this->belongsTo(CleaningCompany::class, 'cleaning_company_id');
        }

        /**
         * Verification check for safety data availability.
         */
        public function hasSafetySheet(): bool
        {
            return !empty($this->safety_data);
        }

        /**
         * Replenishment indicator based on min_threshold.
         */
        public function needsRestock(): bool
        {
            return $this->stock_quantity <= $this->min_threshold;
        }

        /**
         * Stock consumption logic.
         */
        public function decrementStock(int $amount): bool
        {
            if ($this->stock_quantity >= $amount) {
                $this->decrement('stock_quantity', $amount);
                return true;
            }
            return false;
        }
}
