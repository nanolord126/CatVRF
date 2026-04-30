<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\FarmDirect\Agro\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AgroFarm extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'agro_farms';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'address',
        'inn',
        'kpp',
        'description',
        'specialization', // jsonb: [meat, milk, grain]
        'geo_location',   // Point/jsonb
        'is_verified',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'specialization' => 'array',
        'is_verified' => 'boolean',
        'tags' => 'json',
    ];

    /**
     * Выполнить операцию
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function products(): HasMany
    {
        return $this->hasMany(AgroProduct::class, 'farm_id');
    }
}
