<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class CosmeticProduct extends Model
{
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
