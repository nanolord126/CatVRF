<?php

declare(strict_types=1);

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Payout - выплата бизнесу или партнёру
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $business_group_id
 * @property int $user_id
 * @property string $uuid
 * @property int $amount Сумма в копейках
 * @property int $tax_amount Налог в копейках
 * @property int $net_amount Чистая сумма в копейках
 * @property string $contract_type Тип контракта: standard, gph (ГПХ), uip (УИП)
 * @property string $status pending/processing/completed/failed/cancelled
 * @property string $payment_method Метод оплаты: bank_transfer, card, wallet
 * @property string $correlation_id Идентификатор корреляции
 * @property string|null $notes Примечания
 * @property string|null $rejection_reason Причина отклонения
 * @property array $tags Теги для аналитики
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Payout extends Model
{
    use SoftDeletes;

    protected $table = 'payouts';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'user_id',
        'uuid',
        'amount',
        'tax_amount',
        'net_amount',
        'contract_type',
        'status',
        'payment_method',
        'correlation_id',
        'notes',
        'rejection_reason',
        'tags',
    ];

    protected $casts = [
        'amount' => 'integer',
        'tax_amount' => 'integer',
        'net_amount' => 'integer',
        'tags' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'correlation_id',
    ];

    /**
     * Создаёт выплату с расчётом налогов.
     * 
     * @param int $tenantId ID тенанта
     * @param int $userId ID пользователя
     * @param int $amountCopeki Сумма в копейках
     * @param string $contractType Тип контракта
     * @param string $correlationId Идентификатор корреляции
     * @return self
     */
    public static function createWithTax(
        int $tenantId,
        int $userId,
        int $amountCopeki,
        string $contractType = 'standard',
        string $correlationId = '',
    ): self {
        $taxAmount = 0;
        $netAmount = $amountCopeki;
        $notes = '';

        // НДФЛ в зависимости от типа контракта
        if ($contractType === 'gph') {
            // ГПХ: НДФЛ 13% + социальные взносы 30% (ответственность ИП)
            $taxAmount = (int) round($amountCopeki * 0.13);
            $netAmount = $amountCopeki - $taxAmount;
            $notes = 'НДФЛ 13% удержано. Социальные взносы на ответственности физлица.';
        } elseif ($contractType === 'uip') {
            // УИП: НДФЛ 30%
            $taxAmount = (int) round($amountCopeki * 0.30);
            $netAmount = $amountCopeki - $taxAmount;
            $notes = 'НДФЛ 30% удержано (УИП).';
        }

        return self::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'uuid' => \Illuminate\Support\Str::uuid(),
            'amount' => $amountCopeki,
            'tax_amount' => $taxAmount,
            'net_amount' => $netAmount,
            'contract_type' => $contractType,
            'status' => 'pending',
            'correlation_id' => $correlationId,
            'notes' => $notes,
        ]);
    }

    /**
     * Relationship: Tenant
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    /**
     * Relationship: User
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
