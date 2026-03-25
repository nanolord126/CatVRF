<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class ConflictResolutionService
{
    private const CONFLICT_CACHE_TTL = 1800; // 30 minutes

    /**
     * Регистрирует изменение в истории конфликтов
     */
    public function recordEdit(
        string $sessionId,
        int $userId,
        string $documentType,
        int $documentId,
        int $tenantId,
        array $editData,
        string $correlationId = null
    ): array {
        $correlationId ??= Str::uuid()->toString();

        try {
            $editId = Str::uuid()->toString();
            $editKey = "collab:edit:{$tenantId}:{$documentType}:{$documentId}:{$editId}";

            $edit = [
                'edit_id' => $editId,
                'session_id' => $sessionId,
                'user_id' => $userId,
                'timestamp' => now()->toIso8601String(),
                'operation' => $editData['operation'] ?? 'update',
                'position' => $editData['position'] ?? null,
                'content' => $editData['content'] ?? null,
                'element_id' => $editData['element_id'] ?? null,
                'version' => $editData['version'] ?? 1,
                'correlation_id' => $correlationId,
            ];

            $this->cache->put($editKey, $edit, self::CONFLICT_CACHE_TTL);

            // Добавляем в историю редактирования
            $historyKey = "collab:history:{$tenantId}:{$documentType}:{$documentId}";
            $history = $this->cache->get($historyKey, []);
            $history[] = $editId;

            // Сохраняем только последние 100 изменений
            if (count($history) > 100) {
                $oldestEditId = array_shift($history);
                $this->cache->forget("collab:edit:{$tenantId}:{$documentType}:{$documentId}:{$oldestEditId}");
            }

            $this->cache->put($historyKey, $history, self::CONFLICT_CACHE_TTL);

            $this->log->channel('audit')->debug('Edit recorded', [
                'correlation_id' => $correlationId,
                'edit_id' => $editId,
                'user_id' => $userId,
            ]);

            return $edit;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Failed to record edit', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Обнаруживает конфликты редактирования
     */
    public function detectConflicts(
        string $documentType,
        int $documentId,
        int $tenantId,
        string $lastKnownVersion
    ): array {
        $historyKey = "collab:history:{$tenantId}:{$documentType}:{$documentId}";
        $editIds = $this->cache->get($historyKey, []);

        $conflicts = [];

        foreach ($editIds as $editId) {
            $editKey = "collab:edit:{$tenantId}:{$documentType}:{$documentId}:{$editId}";
            $edit = $this->cache->get($editKey);

            if ($edit && $edit['version'] > (int)$lastKnownVersion) {
                $conflicts[] = $edit;
            }
        }

        return $conflicts;
    }

    /**
     * Разрешает конфликт (использует последнее изменение)
     */
    public function resolveConflict(
        array $edit1,
        array $edit2,
        string $resolution = 'latest' // latest, merge, custom
    ): array {
        if ($resolution === 'latest') {
            $timestamp1 = strtotime($edit1['timestamp']);
            $timestamp2 = strtotime($edit2['timestamp']);

            return $timestamp1 > $timestamp2 ? $edit1 : $edit2;
        }

        if ($resolution === 'merge') {
            // Простое объединение (для текста)
            if ($edit1['element_id'] === $edit2['element_id']) {
                return [
                    'operation' => 'merge',
                    'content' => $edit1['content'] . $edit2['content'],
                    'element_id' => $edit1['element_id'],
                    'position' => $edit1['position'],
                ];
            }
        }

        return $edit1;
    }

    /**
     * Получает историю редактирования
     */
    public function getEditHistory(
        string $documentType,
        int $documentId,
        int $tenantId,
        int $limit = 50
    ): Collection {
        $historyKey = "collab:history:{$tenantId}:{$documentType}:{$documentId}";
        $editIds = $this->cache->get($historyKey, []);

        $edits = collect();

        // Берём последние $limit отредактированных
        $recentEditIds = array_slice($editIds, -$limit);

        foreach ($recentEditIds as $editId) {
            $editKey = "collab:edit:{$tenantId}:{$documentType}:{$documentId}:{$editId}";
            $edit = $this->cache->get($editKey);

            if ($edit) {
                $edits->push($edit);
            }
        }

        return $edits;
    }

    /**
     * Проверяет, заблокирован ли документ для редактирования
     */
    public function isDocumentLocked(
        string $documentType,
        int $documentId,
        int $tenantId
    ): bool {
        $lockKey = "collab:lock:{$tenantId}:{$documentType}:{$documentId}";

        return $this->cache->has($lockKey);
    }

    /**
     * Блокирует документ
     */
    public function lockDocument(
        string $documentType,
        int $documentId,
        int $tenantId,
        int $lockDurationSeconds = 300
    ): bool {
        $lockKey = "collab:lock:{$tenantId}:{$documentType}:{$documentId}";
        $this->cache->put($lockKey, true, $lockDurationSeconds);

        return true;
    }

    /**
     * Разблокирует документ
     */
    public function unlockDocument(
        string $documentType,
        int $documentId,
        int $tenantId
    ): bool {
        $lockKey = "collab:lock:{$tenantId}:{$documentType}:{$documentId}";
        $this->cache->forget($lockKey);

        return true;
    }
}
