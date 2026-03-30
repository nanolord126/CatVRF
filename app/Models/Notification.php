<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperNotification
 */
class Notification extends Model
{
    use SoftDeletes;

    protected $table = 'notifications';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'channels',
        'status',
        'correlation_id',
        'sent_at',
        'read_at',
        'delivered_at',
        'failed_at',
        'error_message',
        'retry_count',
        'max_retries',
    ];

    protected $casts = [
        'data' => AsCollection::class,
        'channels' => 'json',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Отношение к User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Отношение к Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope: не прочитанные
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope: прочитанные
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope: отправленные
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope: доставленные
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope: ошибки
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: по типу
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: по correlation ID
     */
    public function scopeWithCorrelationId($query, string $id)
    {
        return $query->where('correlation_id', $id);
    }

    /**
     * Отметить как прочитанное
     */
    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Отметить как отправленное
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Отметить как доставленное
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Отметить как неудачное
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Можно ли повторить отправку
     */
    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries && $this->status === 'failed';
    }

    /**
     * Увеличить счётчик повторов
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Получить данные для frontend
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->uuid,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data?->toArray(),
            'channels' => $this->channels,
            'status' => $this->status,
            'read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
