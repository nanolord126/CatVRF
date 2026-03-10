<?php

namespace App\Services\Common\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HelpdeskService
{
    /**
     * Создать тикет в администрацию (Platform Support)
     */
    public function openTicket(?Tenant $tenant, int $userId, array $data): int
    {
        $correlationId = Str::uuid()->toString();

        return DB::table('support_tickets')->insertGetId([
            'tenant_id' => $tenant?->id,
            'user_id' => $userId,
            'subject' => $data['subject'],
            'category' => $data['category'], // billing, fraud, tech
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'correlation_id' => $correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Отправить сообщение в чат поддержки
     */
    public function addMessage(int $ticketId, int $senderId, string $message, bool $isAdmin = false): void
    {
        DB::table('support_messages')->insert([
            'support_ticket_id' => $ticketId,
            'sender_id' => $senderId,
            'message' => $message,
            'is_admin_reply' => $isAdmin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Обновляем метку времени тикета
        DB::table('support_tickets')->where('id', $ticketId)->update(['updated_at' => now()]);
    }

    /**
     * Создать или получить чат между двумя субъектами (Бизнес-Бизнес или Юзер-Бизнес)
     */
    public function findOrCreatePlatformChat(int $fromUserId, int $toUserId, ?string $fromTenantId, ?string $toTenantId, ?string $context = null, ?int $contextId = null): int
    {
        // Сортировка ID для формирования уникального хеша диалога (Chat Isolation)
        $participants = [$fromUserId, $toUserId];
        sort($participants);
        $chatHash = md5(implode(':', $participants));

        $chat = DB::table('platform_chats')->where('chat_hash', $chatHash)->first();

        if (!$chat) {
            return DB::table('platform_chats')->insertGetId([
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'from_tenant_id' => $fromTenantId,
                'to_tenant_id' => $toTenantId,
                'context_type' => $context,
                'context_id' => $contextId,
                'chat_hash' => $chatHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $chat->id;
    }

    /**
     * Отправить сообщение в B2B/B2C чат
     */
    public function sendChatMessage(int $chatId, int $senderId, string $message): void
    {
        DB::table('platform_chat_messages')->insert([
            'platform_chat_id' => $chatId,
            'sender_id' => $senderId,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('platform_chats')->where('id', $chatId)->update(['updated_at' => now()]);
    }
}
