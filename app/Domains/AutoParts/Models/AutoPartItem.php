<?php declare(strict_types=1);

namespace App\Domains\AutoParts\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class AutoPartItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'auto_part_items';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'name', 'description', 'part_number', 'brand', 'category',
        'price', 'current_stock',
        'compatible_vehicles', 'is_original', 'has_warranty',
        'warranty_months', 'photo_url', 'status', 'tags',
    ];
    protected $casts = [
        'price'               => 'int',
        'current_stock'       => 'int',
        'warranty_months'     => 'int',
        'is_original'         => 'boolean',
        'has_warranty'        => 'boolean',
        'compatible_vehicles' => 'array',
        'tags'                => 'json',
    ];

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}