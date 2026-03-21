<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class Medicine extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'medicines';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'name', 'manufacturer', 'type', 'strength_mg', 'form',
        'price', 'current_stock', 'prescription_required',
        'vet_certificate_num', 'expiry_date', 'batch_num',
        'storage_temp_min', 'storage_temp_max', 'photo_url', 'status', 'tags',
    ];
    protected $casts = [
        'price'                 => 'int',
        'current_stock'         => 'int',
        'strength_mg'           => 'float',
        'prescription_required' => 'boolean',
        'expiry_date'           => 'date',
        'storage_temp_min'      => 'int',
        'storage_temp_max'      => 'int',
        'tags'                  => 'json',
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
