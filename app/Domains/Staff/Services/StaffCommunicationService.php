<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * StaffCommunicationService — сервис коммуникации сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Управляет внутренним чатом, объявлениями, обсуждениями проектов,
 * интеграцией с Slack/Teams.
 */
final class StaffCommunicationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Отправляет сообщение в чат.
     */
    public function sendChatMessage(array $data): array
    {
        $this->fraud->check(
            userId: $data['sender_id'],
            operationType: 'chat_message_send',
            amount: 0,
            correlationId: (string) \Illuminate\Support\Str::uuid()
        );

        $message = DB::transaction(function () use ($data) {
            return \App\Domains\Staff\Domain\Entities\StaffChatMessage::create([
                'tenant_id' => $data['tenant_id'],
                'sender_id' => $data['sender_id'],
                'channel_id' => $data['channel_id'] ?? null,
                'recipient_id' => $data['recipient_id'] ?? null,
                'message' => $data['message'],
                'message_type' => $data['message_type'] ?? 'text',
                'attachments' => $data['attachments'] ?? [],
                'is_read' => false,
                'read_at' => null,
                'reply_to_message_id' => $data['reply_to_message_id'] ?? null,
            ]);
        });

        Cache::tags(['staff_communication'])->flush();

        // Broadcast via WebSocket
        \Illuminate\Support\Facades\Broadcast::channel('staff-chat', function ($user) {
            return true;
        });

        $this->logCreated('staff_chat_message', $message->id, [
            'sender_id' => $data['sender_id'],
            'channel_id' => $data['channel_id'] ?? null,
        ]);

        return $message->toArray();
    }

    /**
     * Создаёт объявление.
     */
    public function createAnnouncement(array $data): array
    {
        $this->fraud->check(
            userId: $data['author_id'],
            operationType: 'announcement_create',
            amount: 0,
            correlationId: (string) \Illuminate\Support\Str::uuid()
        );

        $announcement = \App\Domains\Staff\Domain\Entities\StaffAnnouncement::create([
            'tenant_id' => $data['tenant_id'],
            'author_id' => $data['author_id'],
            'title' => $data['title'],
            'content' => $data['content'],
            'priority' => $data['priority'] ?? 'normal',
            'target_audience' => $data['target_audience'] ?? 'all',
            'target_departments' => $data['target_departments'] ?? [],
            'target_roles' => $data['target_roles'] ?? [],
            'published_at' => $data['published_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
            'attachments' => $data['attachments'] ?? [],
            'is_pinned' => $data['is_pinned'] ?? false,
            'require_acknowledgment' => $data['require_acknowledgment'] ?? false,
        ]);

        Cache::tags(['staff_communication', 'staff_announcements'])->flush();

        $this->logCreated('staff_announcement', $announcement->id, [
            'title' => $data['title'],
            'priority' => $data['priority'] ?? 'normal',
        ]);

        return $announcement->toArray();
    }

    /**
     * Создаёт обсуждение проекта.
     */
    public function createProjectDiscussion(array $data): array
    {
        $this->fraud->check(
            userId: $data['creator_id'],
            operationType: 'project_discussion_create',
            amount: 0,
            correlationId: (string) \Illuminate\Support\Str::uuid()
        );

        $discussion = \App\Domains\Staff\Domain\Entities\StaffProjectDiscussion::create([
            'tenant_id' => $data['tenant_id'],
            'project_id' => $data['project_id'],
            'creator_id' => $data['creator_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'active',
            'participants' => $data['participants'] ?? [],
            'is_private' => $data['is_private'] ?? false,
        ]);

        Cache::tags(['staff_communication'])->flush();

        $this->logCreated('staff_project_discussion', $discussion->id, [
            'project_id' => $data['project_id'],
            'title' => $data['title'],
        ]);

        return $discussion->toArray();
    }

    /**
     * Добавляет комментарий к обсуждению.
     */
    public function addDiscussionComment(array $data): array
    {
        $comment = \App\Domains\Staff\Domain\Entities\StaffDiscussionComment::create([
            'tenant_id' => $data['tenant_id'],
            'discussion_id' => $data['discussion_id'],
            'author_id' => $data['author_id'],
            'comment' => $data['comment'],
            'attachments' => $data['attachments'] ?? [],
            'parent_comment_id' => $data['parent_comment_id'] ?? null,
        ]);

        Cache::tags(['staff_communication'])->flush();

        $this->logCreated('staff_discussion_comment', $comment->id, [
            'discussion_id' => $data['discussion_id'],
            'author_id' => $data['author_id'],
        ]);

        return $comment->toArray();
    }

    /**
     * Интегрируется с Slack.
     */
    public function sendToSlack(array $data): void
    {
        $this->fraud->check(
            userId: $data['sender_id'],
            operationType: 'slack_integration',
            amount: 0,
            correlationId: (string) \Illuminate\Support\Str::uuid()
        );

        // Dispatch job to send message to Slack
        \App\Jobs\Staff\SendSlackMessageJob::dispatch(
            $data['channel'] ?? '#general',
            $data['message'],
            $data['attachments'] ?? []
        );

        $this->logAction('slack_message_sent', [
            'entity_type' => 'staff',
            'entity_id' => $data['sender_id'],
            'channel' => $data['channel'] ?? '#general',
        ]);
    }

    /**
     * Интегрируется с Microsoft Teams.
     */
    public function sendToTeams(array $data): void
    {
        $this->fraud->check(
            userId: $data['sender_id'],
            operationType: 'teams_integration',
            amount: 0,
            correlationId: (string) \Illuminate\Support\Str::uuid()
        );

        // Dispatch job to send message to Teams
        \App\Jobs\Staff\SendTeamsMessageJob::dispatch(
            $data['team_id'] ?? null,
            $data['channel_id'] ?? null,
            $data['message'],
            $data['attachments'] ?? []
        );

        $this->logAction('teams_message_sent', [
            'entity_type' => 'staff',
            'entity_id' => $data['sender_id'],
            'team_id' => $data['team_id'] ?? null,
        ]);
    }

    /**
     * Получает историю чата.
     */
    public function getChatHistory(int $channelId, ?int $limit = 50): array
    {
        $cacheKey = "chat_history:{$channelId}:{$limit}";

        return Cache::tags(['staff_communication'])->remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($channelId, $limit) {
                return \App\Domains\Staff\Domain\Entities\StaffChatMessage::where('channel_id', $channelId)
                    ->with('sender')
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'sender_name' => $m->sender->full_name ?? 'Unknown',
                        'message' => $m->message,
                        'message_type' => $m->message_type,
                        'created_at' => $m->created_at->toIso8601String(),
                        'is_read' => $m->is_read,
                    ])
                    ->reverse()
                    ->toArray();
            }
        );
    }

    /**
     * Получает объявления.
     */
    public function getAnnouncements(int $tenantId, ?string $priority = null): array
    {
        $cacheKey = "announcements:{$tenantId}:" . ($priority ?? 'all');

        return Cache::tags(['staff_communication', 'staff_announcements'])->remember(
            $cacheKey,
            now()->addMinutes(15),
            function () use ($tenantId, $priority) {
                $query = \App\Domains\Staff\Domain\Entities\StaffAnnouncement::where('tenant_id', $tenantId)
                    ->where('published_at', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });

                if ($priority) {
                    $query->where('priority', $priority);
                }

                return $query->with('author')
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('published_at')
                    ->limit(20)
                    ->get()
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'title' => $a->title,
                        'content' => $a->content,
                        'priority' => $a->priority,
                        'author_name' => $a->author->full_name ?? 'Unknown',
                        'published_at' => $a->published_at->toIso8601String(),
                        'is_pinned' => $a->is_pinned,
                    ])
                    ->toArray();
            }
        );
    }

    /**
     * Помечает сообщения как прочитанные.
     */
    public function markMessagesAsRead(int $staffId, array $messageIds): void
    {
        \App\Domains\Staff\Domain\Entities\StaffChatMessage::whereIn('id', $messageIds)
            ->where('recipient_id', $staffId)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        Cache::tags(['staff_communication'])->flush();

        $this->logAction('messages_marked_read', [
            'entity_type' => 'staff_chat_message',
            'entity_id' => $staffId,
            'count' => count($messageIds),
        ]);
    }

    /**
     * Получает непрочитанные сообщения.
     */
    public function getUnreadMessages(int $staffId): array
    {
        $cacheKey = "unread_messages:{$staffId}";

        return Cache::tags(['staff_communication'])->remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($staffId) {
                return \App\Domains\Staff\Domain\Entities\StaffChatMessage::where('recipient_id', $staffId)
                    ->where('is_read', false)
                    ->with('sender')
                    ->latest()
                    ->limit(50)
                    ->get()
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'sender_name' => $m->sender->full_name ?? 'Unknown',
                        'message' => $m->message,
                        'created_at' => $m->created_at->toIso8601String(),
                    ])
                    ->toArray();
            }
        );
    }
}
