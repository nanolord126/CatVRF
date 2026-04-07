<?php declare(strict_types=1);

namespace App\Models;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Заявка на выплату (Payout Request)
 *
 * КАНОН 2026 - Production Ready
 * Хранит информацию о запросе пользователя/бизнеса на вывод средств
 *
 * Статусы:
 * - pending: ожидает обработки
 * - processing: обрабатывается платёжной системой
 * - completed: успешно отправлена
 * - failed: ошибка при обработке
 * - cancelled: отменена пользователем или админом
 */
final class PayoutRequest extends Model
{
    protected $table = 'payout_requests';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'amount',
        'status',
        'bank_details',
        'correlation_id',
        'tags',
        'cancellation_reason',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'bank_details' => 'json',
        'tags' => 'json',
        'metadata' => 'json',
    ];

    /**
     * Global scope: tenant scoping
     */
    protected static function booted()
    {
        static::addGlobalScope('tenant', function ($query) {
            if ($this->guard->check() && $this->guard->user()->tenant_id) {
                $query->where('tenant_id', $this->guard->user()->tenant_id);
            }
        });
    }

    /**
     * Relations
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
