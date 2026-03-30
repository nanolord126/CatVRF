<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CosmeticProduct extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

        protected $fillable = [
            'tenant_id', 'salon_id', 'brand', 'name',
            'description', 'category', 'volume', 'price',
            'stock', 'is_available', 'is_professional',
            'uuid', 'correlation_id', 'tags',
        ];

        protected $casts = [
            'tags' => 'json', 'is_available' => 'boolean',
            'is_professional' => 'boolean', 'price' => 'decimal:2',
            'stock' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) =>
                $q->where('tenant_id', tenant()->id ?? 0)
            );
        }

        public function salon(): BelongsTo
        {
            return $this->belongsTo(BeautySalon::class, 'salon_id');
        }
}
