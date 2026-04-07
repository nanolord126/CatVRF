<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class PaymentTransactionModel
 * 
 * Persistent Eloquent Model for the Payment entity mapped to the `payment_transactions` table.
 * Adheres strictly to the architectural constraints, tenant scoping, and structured casts.
 *
 * @property string $id
 * @property int $tenant_id
 * @property int $user_id
 * @property int $amount
 * @property string $idempotency_key
 * @property string $status
 * @property string|null $provider_payment_id
 * @property string|null $payment_url
 * @property string|null $correlation_id
 * @property array $metadata_json
 * @property bool $recurrent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static Builder query()
 */
final class PaymentTransactionModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Exact table name following the architectural canon.
     * @var string
     */
    protected $table = 'payment_transactions';

    /**
     * Explicitly stating the primary key.
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Since UUIDs are used, auto-incrementing is disabled.
     * @var bool
     */
    public $incrementing = false;

    /**
     * ID type is string (UUID).
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Mass assignable attributes cleanly mapped.
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'amount',
        'idempotency_key',
        'status',
        'provider_payment_id',
        'payment_url',
        'correlation_id',
        'metadata_json',
        'recurrent',
    ];

    /**
     * Protected attributes casting definitions ensuring strong typing when reading out of the database.
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'user_id'   => 'integer',
        'amount'    => 'integer',
        'recurrent' => 'boolean',
        'metadata_json' => 'array',
    ];

    /**
     * Hidden attributes, useful if model happens to be serialized into array context.
     * Keeps internal or provider specific secrets hidden.
     * @var array<int, string>
     */
    protected $hidden = [
        'metadata_json',
    ];

    /**
     * Global scoping method strictly required by canon rules:
     * "booted() метод с global scope tenant_id"
     */
    protected static function booted(): void
    {
        // Safe check block for CLI usages avoiding strict tenant boundaries failing during migrations
        if (!app()->runningInConsole() && function_exists('tenant') && tenant() !== null) {
            static::addGlobalScope('tenant', function (Builder $builder) {
                $tenantId = tenant()->id ?? null;
                if ($tenantId !== null) {
                    $builder->where('tenant_id', $tenantId);
                }
            });
        }
    }
}
