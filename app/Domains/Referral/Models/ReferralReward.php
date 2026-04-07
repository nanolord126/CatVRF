<?php

declare(strict_types=1);

namespace App\Domains\Referral\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Referral\Enums\ReferralRewardType;

/**
 * Абсолютно строгая модель хранения факта выплаты (или ожидания) реферального вознаграждения.
 *
 * Категорически фиксирует получателя (recipient), сумму (amount) в копейках,
 * тип выплаты и связывает ее с идентификатором корреляции для полного 100% аудита (ФЗ-152).
 */
final class ReferralReward extends Model
{
    /**
     * @var string $table Исключительно точно заданное название физической таблицы хранения наград.
     */
    protected $table = 'referral_rewards';

    /**
     * @var array<int, string> Полноценный список разрешенных к заполнению атрибутов.
     */
    protected $fillable = [
        'uuid',
        'correlation_id',
        'referral_id',
        'recipient_type',
        'recipient_id',
        'amount',
        'type',
        'status',
        'credited_at',
        'withdrawn_at',
        'correlation_id',
        'tenant_id',
    ];

    /**
     * @var array<string, string> Безусловное приведение внутренних данных из базы к PHP-типам.
     */
    protected $casts = [
        'amount' => 'integer',
        'type' => ReferralRewardType::class,
        'credited_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


    /**
     * Возвращает исключительно сильную связь с главной сущностью Referral.
     *
     * @return BelongsTo
     */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class, 'referral_id', 'id');
    }
}
