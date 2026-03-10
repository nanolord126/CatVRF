<?php

namespace App\Domains\Common\Services\Chat;

use App\Models\User;
use App\Domains\Common\Models\ChatArchive;
use Illuminate\Support\{Carbon, Facades, Str};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use Throwable;

/**
 * Chat Compliance System (CCS).
 * Production: 374-ФЗ compliance, full audit trail, error handling, Sentry integration.
 */
class ChatSecurityService
{
    protected array $forbiddenPatterns = [
        'phones' => '/(\+?\d[\d\(\)\s-]{8,14}\d)/',
        'links' => '/(https?:\/\/)?(([da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*)?\/?/',
        'tg_wa' => '/(@[a-zA-Z0-9_]{4,})|(t\.me\/[a-zA-Z0-9_]{4,})|(wa\.me\/\d+)/i',
        'emails' => '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i',
    ];

    private string $correlationId;
    private ?string $tenantId;

    public function __construct()
    {
        $this->correlationId = Str::uuid();
        $this->tenantId = Auth::guard('tenant')?->id();
    }

    /**
     * Обработка сообщения перед отправкой.
     */
    public function secureProcessMessage(User $sender, User $receiver, string $content): string
    {
        $this->correlationId = Str::uuid();

        try {
            Log::channel('chat')->info('ChatSecurity: processing message', [
                'correlation_id' => $this->correlationId,
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'content_length' => strlen($content),
            ]);

            $sanitizedContent = $this->sanitizeContacts($content);
            $isSanitized = ($sanitizedContent !== $content);

            $archive = $this->archiveMessage($sender, $receiver, $content, $sanitizedContent, $isSanitized);

            if ($isSanitized) {
                Log::warning('ChatSecurity: contact swap attempt', [
                    'correlation_id' => $this->correlationId,
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'archive_id' => $archive->id,
                ]);

                AuditLog::create([
                    'entity_type' => 'ChatArchive',
                    'entity_id' => $archive->id,
                    'action' => 'sanitized_contact_attempt',
                    'user_id' => $sender->id,
                    'tenant_id' => $this->tenantId,
                    'correlation_id' => $this->correlationId,
                    'changes' => [],
                    'metadata' => [
                        'original_length' => strlen($content),
                        'sanitized_length' => strlen($sanitizedContent),
                        'patterns_found' => $this->detectViolationPatterns($content),
                    ],
                ]);
            }

            Log::channel('chat')->info('ChatSecurity: message processed', [
                'correlation_id' => $this->correlationId,
                'archive_id' => $archive->id,
                'is_sanitized' => $isSanitized,
            ]);

            return $sanitizedContent;
        } catch (Throwable $e) {
            Log::error('ChatSecurity: message processing failed', [
                'correlation_id' => $this->correlationId,
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Очистка текста от контактных данных.
     */
    protected function sanitizeContacts(string $text): string
    {
        foreach ($this->forbiddenPatterns as $pattern) {
            $text = preg_replace($pattern, '[КОНТАКТЫ ЗАПРЕЩЕНЫ ПРАВИЛАМИ МАРКЕТПЛЕЙСА]', $text);
        }
        return $text;
    }

    /**
     * Обнаружение нарушающих паттернов.
     */
    protected function detectViolationPatterns(string $text): array
    {
        $found = [];
        foreach ($this->forbiddenPatterns as $name => $pattern) {
            if (preg_match($pattern, $text)) {
                $found[] = $name;
            }
        }
        return $found;
    }

    /**
     * Хранение сообщения в течение 3 лет (374-ФЗ).
     */
    protected function archiveMessage(User $sender, User $receiver, string $original, string $sanitized, bool $isSanitized): ChatArchive
    {
        try {
            $contentHash = hash('sha256', $original);
            $retentionUntil = Carbon::now()->addYears(3);

            $archive = ChatArchive::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'content' => $original,
                'sanitized_content' => $sanitized,
                'is_sanitized' => $isSanitized,
                'content_hash' => $contentHash,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'sent_at' => Carbon::now(),
                'compliance_checked_at' => Carbon::now(),
                'correlation_id' => $this->correlationId,
                'tenant_id' => $this->tenantId,
                'metadata' => [
                    'original_length' => strlen($original),
                    'sanitized_length' => strlen($sanitized),
                    'retention_until' => $retentionUntil->toIso8601String(),
                    'regulation' => '374-ФЗ (Закон Яровой)',
                ]
            ]);

            AuditLog::create([
                'entity_type' => ChatArchive::class,
                'entity_id' => $archive->id,
                'action' => 'created',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                ],
                'metadata' => [
                    'is_sanitized' => $isSanitized,
                    'content_hash' => $contentHash,
                    'retention_years' => 3,
                ],
            ]);

            return $archive;
        } catch (Throwable $e) {
            Log::error('ChatSecurity: archive creation failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Поиск по архиву (для запросов от ОРМ).
     */
    public function searchInHistory(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        try {
            Log::channel('chat')->info('ChatSecurity: history search', [
                'correlation_id' => $this->correlationId,
                'filters' => array_keys($filters),
            ]);

            $query = ChatArchive::query();

            if (isset($filters['user_id'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('sender_id', $filters['user_id'])
                      ->orWhere('receiver_id', $filters['user_id']);
                });
            }

            if (isset($filters['keyword'])) {
                $query->where('content', 'like', "%{$filters['keyword']}%");
            }

            if (isset($filters['date_from'])) {
                $query->whereDate('sent_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->whereDate('sent_at', '<=', $filters['date_to']);
            }

            $results = $query->get();

            Log::channel('chat')->info('ChatSecurity: history search completed', [
                'correlation_id' => $this->correlationId,
                'results_count' => $results->count(),
            ]);

            return $results;
        } catch (Throwable $e) {
            Log::error('ChatSecurity: history search failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            return collect([]);
        }
    }
}
