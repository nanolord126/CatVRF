declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final /**
 * PharmacyOrder
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PharmacyOrder extends Model
{
    protected $table = 'pharmacy_orders';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'pharmacy_id',
        'uuid',
        'total_amount',
        'status',
        'idempotency_key',
        'tags',
        'correlation_id'
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'tags' => 'json'
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacyOrderItem::class, 'order_id');
    }
}