declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Cars\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final /**
 * CarOrder
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CarOrder extends Model
{
    protected $table = 'car_orders';

    protected $fillable = [
        'tenant_id',
        'car_id',
        'client_id',
        'uuid',
        'amount',
        'status',
        'idempotency_key',
        'tags',
        'correlation_id'
    ];

    protected $casts = [
        'tags' => 'json',
        'amount' => 'integer'
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function (Builder $builder) {
            $builder->where('tenant_id', tenant()->id ?? 0);
        });

        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
        });
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }
}