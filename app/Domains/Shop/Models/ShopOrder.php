<?php
declare(strict_types=1);

namespace App\Domains\Shop\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $user_id
 * @property int $total_amount_kopeks
 * @property string $status
 */
final class ShopOrder extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'shop_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'total_amount_kopeks',
        'status',
        'payment_status',
        'shipping_address',
        'correlation_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
