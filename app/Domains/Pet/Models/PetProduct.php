<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PetProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pet_products';

    protected $fillable = [
        'tenant_id',
        'clinic_id',
        'name',
        'description',
        'sku',
        'pet_type',
        'category',
        'price',
        'cost_price',
        'current_stock',
        'min_stock_threshold',
        'tags',
        'is_active',
        'rating',
        'review_count',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'tags' => 'collection',
        'price' => 'float',
        'cost_price' => 'float',
        'rating' => 'float',
        'current_stock' => 'integer',
        'min_stock_threshold' => 'integer',
        'review_count' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = ['correlation_id'];

    public function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(PetClinic::class);
    }
}
