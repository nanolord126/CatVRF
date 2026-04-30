<?php

declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class Medicine extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'pharmacy_medicines';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'correlation_id',
        'name',
        'sku',
        'barcode',
        'description',
        'active_ingredient',
        'dosage',
        'form_factor',
        'is_prescription_required',
        'is_refrigerated',
        'price_kopecks',
        'current_stock',
        'min_stock_threshold',
        'tags',
        'meta',
    ];

    protected $casts = [
        'is_prescription_required' => 'boolean',
        'is_refrigerated' => 'boolean',
        'price_kopecks' => 'integer',
        'current_stock' => 'integer',
        'tags' => 'array',
        'meta' => 'array',
    ];

    /**
     * Выполнить операцию
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
