declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $b2b_entertainment_storefront_id
 * @property int|null $user_id
 * @property string $order_number
 * @property string $company_contact_person
 * @property string $company_phone
 * @property array|null $items
 * @property float $total_amount
 * @property float $commission_amount
 * @property string $status
 * @property string|null $rejection_reason
 * @property string|null $correlation_id
 * @property array|null $tags
 */
final class B2BEntertainmentOrder extends Model
{
    use SoftDeletes;

    protected $table = 'b2b_entertainment_orders';

    protected $fillable = [
        'uuid', 'tenant_id', 'b2b_entertainment_storefront_id', 'user_id', 'order_number',
        'company_contact_person', 'company_phone', 'items', 'total_amount',
        'commission_amount', 'status', 'rejection_reason', 'correlation_id', 'tags'
    ];

    protected $casts = [
        'items' => 'json',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(B2BEntertainmentStorefront::class, 'b2b_entertainment_storefront_id');
    }
}
