<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerPortfolioItem extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'flower_portfolio';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'shop_id',
            'image_path',
            'title',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'uuid' => 'string',
            'tags' => 'json',
        ];

        protected $hidden = [
            'id',
            'tenant_id',
        ];

        /**
         * КАНОН 2026: Изоляция тенанта и автоматика.
         */
        protected static function booted(): void
        {
            static::creating(function (FlowerPortfolioItem $item) {
                $item->uuid = (string) Str::uuid();
                $item->tenant_id = $item->tenant_id ?? (tenant()->id ?? 0);
                $item->correlation_id = $item->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());
            });

            if (function_exists('tenant') && tenant()) {
                static::addGlobalScope('tenant_id', function ($query) {
                    $query->where('tenant_id', tenant()->id);
                });
            }
        }

        /**
         * Магазин, которому принадлежит работа.
         */
        public function shop(): BelongsTo
        {
            return $this->belongsTo(FlowerShop::class, 'shop_id');
        }
}
