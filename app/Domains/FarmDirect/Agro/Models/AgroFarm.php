<?php

declare(strict_types=1);

namespace App\Domains\FarmDirect\Agro\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Модель Фермерского Хозяйства / Агро-предприятия
 */
final class AgroFarm extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

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

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());
        });
    }

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AgroProduct::class, 'farm_id');
    }
}
