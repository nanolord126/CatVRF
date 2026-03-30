<?php declare(strict_types=1);

namespace App\Models\Dental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DentalConsumable extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'dental_consumables';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'name',
            'sku',
            'current_stock',
            'min_threshold',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'tags' => 'json',
            'current_stock' => 'integer',
            'min_threshold' => 'integer',
            'tenant_id' => 'integer',
        ];

        /**
         * Boot logic for automatic UUID and tenant scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());

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
         * Relations: Clinic the consumable belongs to.
         */
        public function clinic(): BelongsTo
        {
            return $this->belongsTo(DentalClinic::class, 'clinic_id');
        }

        /**
         * Deduct stock when used.
         */
        public function deductStock(int $quantity): void
        {
            $this->update(['current_stock' => $this->current_stock - $quantity]);

            if ($this->current_stock <= $this->min_threshold) {
                \Illuminate\Support\Facades\Log::channel('audit')->warning('Low stock for consumable', [
                    'clinic_id' => $this->clinic_id,
                    'name' => $this->name,
                    'sku' => $this->sku,
                    'current' => $this->current_stock,
                    'threshold' => $this->min_threshold,
                    'correlation_id' => $this->correlation_id,
                ]);
            }
        }

        /**
         * Refill stock with audit.
         */
        public function refillStock(int $quantity, int $userId): void
        {
            $oldStock = $this->current_stock;
            $this->update(['current_stock' => $this->current_stock + $quantity]);

            \Illuminate\Support\Facades\Log::channel('audit')->info('Stock refilled', [
                'consumable_id' => $this->id,
                'added' => $quantity,
                'old' => $oldStock,
                'new' => $this->current_stock,
                'user_id' => $userId,
                'correlation_id' => $this->correlation_id,
            ]);
        }
}
