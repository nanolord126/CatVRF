<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DocumentHistory — История изменений документов
 * 
 * Отслеживает все изменения документов для аудита и compliance
 */
final class DocumentHistory extends Model
{
    protected $table = 'document_history';

    protected $fillable = [
        'uuid',
        'document_id',
        'action',
        'old_values',
        'new_values',
        'changes_summary',
        'user_id',
        'user_type',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    // Action types
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_DISTRIBUTED = 'distributed';
    public const ACTION_VALIDATED = 'validated';
    public const ACTION_EXPIRED = 'expired';
    public const ACTION_CLOSED = 'closed';
    public const ACTION_RESTORED = 'restored';

    // User types
    public const USER_TYPE_SUPPLIER = 'supplier';
    public const USER_TYPE_ADMIN = 'admin';
    public const USER_TYPE_SYSTEM = 'system';
    public const USER_TYPE_B2B_CLIENT = 'b2b_client';

    /**
     * Отношения
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Получить описание действия
     */
    public function getActionLabel(): string
    {
        return match($this->action) {
            self::ACTION_CREATED => 'Документ создан',
            self::ACTION_UPDATED => 'Документ обновлён',
            self::ACTION_DELETED => 'Документ удалён',
            self::ACTION_DISTRIBUTED => 'Документ отправлен',
            self::ACTION_VALIDATED => 'Документ валидирован',
            self::ACTION_EXPIRED => 'Срок действия истёк',
            self::ACTION_CLOSED => 'Сертификат закрыт',
            self::ACTION_RESTORED => 'Документ восстановлен',
            default => 'Неизвестное действие',
        };
    }

    /**
     * Создать запись истории
     */
    public static function createLog(
        int $documentId,
        string $action,
        ?int $userId = null,
        ?string $userType = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $summary = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'document_id' => $documentId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changes_summary' => $summary,
            'user_id' => $userId,
            'user_type' => $userType ?? self::USER_TYPE_SYSTEM,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => $metadata,
        ]);
    }
}
