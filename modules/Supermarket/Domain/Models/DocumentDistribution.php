<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

/**
 * DocumentDistribution — Модель распределения документа
 * 
 * Отслеживает кому и когда был отправлен документ
 */
final class DocumentDistribution extends Model
{
    protected $table = 'supermarket_document_distributions';

    protected $fillable = [
        'uuid',
        'document_id',
        'seller_id',
        'customer_id',
        'tenant_id',
        'sent_at',
        'viewed_at',
        'downloaded_at',
        'delivery_method',
        'delivery_status',
        'error_message',
        'notification_sent',
    ];

    protected $casts = [
        'uuid' => 'string',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'downloaded_at' => 'datetime',
        'notification_sent' => 'boolean',
    ];

    // Delivery methods
    public const METHOD_EMAIL = 'email';
    public const METHOD_PUSH = 'push';
    public const METHOD_SMS = 'sms';
    public const METHOD_IN_APP = 'in_app';
    public const METHOD_API = 'api';

    // Delivery status
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_BOUNCED = 'bounced';

    /**
     * Отношения
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Отметить как просмотренный
     */
    public function markAsViewed(): bool
    {
        $this->viewed_at = now();
        return $this->save();
    }

    /**
     * Отметить как скачанный
     */
    public function markAsDownloaded(): bool
    {
        $this->downloaded_at = now();
        return $this->save();
    }

    /**
     * Получить статус для отображения
     */
    public function getStatusLabel(): string
    {
        return match($this->delivery_status) {
            self::STATUS_PENDING => 'Ожидает отправки',
            self::STATUS_SENT => 'Отправлено',
            self::STATUS_DELIVERED => 'Доставлено',
            self::STATUS_FAILED => 'Ошибка',
            self::STATUS_BOUNCED => 'Не доставлено',
            default => 'Неизвестно',
        };
    }
}
