<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff\Communication;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * InternalChatService — Внутренний чат
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Cache with tags
 * - DB transactions
 */
final class InternalChatService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {}

    /**
     * Отправить сообщение
     */
    public function sendMessage(
        int $tenantId,
        int $senderId,
        ?int $receiverId,
        ?int $groupId,
        string $content,
        ?int $userId = null
    ): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $senderId, $receiverId, $groupId, $content, $correlationId, $userId) {
            // TODO: Create message in database
            $messageId = 1; // Placeholder

            Cache::tags(['staff', 'chat', "tenant:{$tenantId}"])->flush();

            $this->logCreated(
                entityType: 'chat_message',
                entityId: $messageId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'sender_id' => $senderId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'message_id' => $messageId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить сообщения чата
     */
    public function getMessages(int $tenantId, ?int $receiverId, ?int $groupId, int $limit = 50): array
    {
        $cacheKey = "staff:chat:{$tenantId}:" . ($receiverId ?? "group:{$groupId}");

        return Cache::tags(['staff', 'chat', "tenant:{$tenantId}"])->remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($tenantId, $receiverId, $groupId, $limit) {
                // TODO: Fetch messages from database
                return [];
            }
        );
    }
}
