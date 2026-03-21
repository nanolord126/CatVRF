<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class HomeServiceJob extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'home_service_jobs';
    protected $fillable = [
        'tenant_id', 'uuid', 'correlation_id',
        'contractor_id', 'client_id', 'service_type', 'datetime',
        'address', 'status', 'price', 'tags', 'meta'
    ];
    protected $casts = [
        'price' => 'int',
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
