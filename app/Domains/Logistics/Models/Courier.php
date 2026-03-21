<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class Courier extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'couriers';
    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'user_id', 'phone', 'vehicle', 'status', 'rating', 'tags', 'meta'
    ];
    protected $casts = [
        'rating' => 'float',
        'tags' => 'json',
        'meta' => 'json',
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
