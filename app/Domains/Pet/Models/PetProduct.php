<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'pet_products';

        /**
         * Поля, доступные для массового заполнения.
         */
        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'name',
            'sku',
            'category', // food, toy, medicine, accessory
            'species_restriction',
            'price',
            'current_stock',
            'min_stock_threshold',
            'correlation_id',
            'tags',
        ];

        /**
         * Приведение типов.
         */
        protected $casts = [
            'tags' => 'json',
            'price' => 'integer',
            'current_stock' => 'integer',
            'min_stock_threshold' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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
            // Global scope для изоляции тенантов
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });

            // Автогенерация UUID и correlation_id
            static::creating(function (PetProduct $model) {
                $model->uuid = $model->uuid ?? (string) \Illuminate\Support\Str::uuid();
                $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());

                if (function_exists('tenant') && tenant()) {
                    $model->tenant_id = $model->tenant_id ?? tenant()->id;
                }
            });
        }

        /**
         * Клиника или магазин, к которому относится товар.
         */
        public function clinic(): BelongsTo
        {
            return $this->belongsTo(PetClinic::class, 'clinic_id');
        }

        /**
         * Проверка: является ли товар лекарственным препаратом.
         */
        public function isMedicine(): bool
        {
            return $this->category === 'medicine';
        }

        /**
         * Проверка: заканчивается ли запас (ниже порога).
         */
        public function isStockLow(): bool
        {
            return $this->current_stock <= $this->min_stock_threshold;
        }

        /**
         * Получение цены в рублях.
         */
        public function getPriceInRubles(): float
        {
            return (float) ($this->price / 100);
        }

        /**
         * Получение форматированной цены.
         */
        public function getFormattedPrice(): string
        {
            return number_format($this->getPriceInRubles(), 2, '.', ' ') . ' ₽';
        }

        /**
         * Проверка совместимости товара с видом животного.
         */
        public function isCompatibleWith(string $species): bool
        {
            if (!$this->species_restriction) {
                return true;
            }
            return strtolower($this->species_restriction) === strtolower($species);
        }

        /**
         * Получить заголовок товара.
         */
        public function getProductTitle(): string
        {
            return sprintf('%s [%s]', $this->name, $this->sku);
        }

        /**
         * Увеличение остатка.
         */
        public function incrementStock(int $amount): bool
        {
            return $this->increment('current_stock', $amount);
        }

        /**
         * Уменьшение остатка (списание).
         */
        public function decrementStock(int $amount): bool
        {
            if ($this->current_stock < $amount) {
                return false;
            }
            return $this->decrement('current_stock', $amount);
        }
}
