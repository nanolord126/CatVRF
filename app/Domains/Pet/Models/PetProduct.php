<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PetProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pet_products';

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

    protected $casts = [
        'tags' => 'json',
        'price' => 'integer',
        'current_stock' => 'integer',
        'min_stock_threshold' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = ['correlation_id'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (PetProduct $model) {
            $model->uuid = $model->uuid ?? (string) \Illuminate\Support\Str::uuid();
            $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());

            if (function_exists('tenant') && tenant()) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(PetClinic::class, 'clinic_id');
    }

    public function isMedicine(): bool
    {
        return $this->category === 'medicine';
    }

    public function isStockLow(): bool
    {
        return $this->current_stock <= $this->min_stock_threshold;
    }
}
