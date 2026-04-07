<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $client_id
 * @property string|null $driver_id
 * @property string $status
 * @property array $pickup_location
 * @property array $dropoff_location
 * @property int|null $price
 * @property string $correlation_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class Ride extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'taxi_rides';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'id',
        'client_id',
        'driver_id',
        'status',
        'pickup_location',
        'dropoff_location',
        'price',
        'correlation_id',
    ];

    protected $casts = [
        'pickup_location' => 'json',
        'dropoff_location' => 'json',
        'price' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

}