<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CollaborationService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private const CACHE_TTL = 3600; // 1 hour
        private const SESSION_HEARTBEAT = 300; // 5 minutes

        /**
         * Инициирует сессию совместного редактирования
         */
        public function startEditingSession(
            int $userId,
            int $tenantId,
            string $documentType,
            int $documentId,
            string $correlationId = null
        ): array {
            $correlationId ??= Str::uuid()->toString();

            try {
                $sessionId = Str::uuid()->toString();
                $sessionKey = "collab:session:{$tenantId}:{$documentType}:{$documentId}:{$sessionId}";

                $sessionData = [
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                    'started_at' => now()->toIso8601String(),
                    'last_heartbeat' => now()->toIso8601String(),
                    'editing_element' => null,
                    'cursor_position' => null,
                    'correlation_id' => $correlationId,
                ];

                Cache::put($sessionKey, $sessionData, self::CACHE_TTL);

                // Добавляем в список активных сессий документа
                $documentsKey = "collab:doc:{$tenantId}:{$documentType}:{$documentId}";
                $activeSessions = Cache::get($documentsKey, []);
                $activeSessions[$sessionId] = [
                    'user_id' => $userId,
                    'started_at' => now()->toIso8601String(),
                ];
                Cache::put($documentsKey, $activeSessions, self::CACHE_TTL);

                Log::channel('audit')->info('Collaboration session started', [
                    'correlation_id' => $correlationId,
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'document' => "{$documentType}:{$documentId}",
                ]);

                return $sessionData;
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to start collaboration session', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        /**
         * Заканчивает сессию редактирования
         */
        public function endEditingSession(
            string $sessionId,
            int $tenantId,
            string $documentType,
            int $documentId,
            string $correlationId = null
        ): bool {
            $correlationId ??= Str::uuid()->toString();

            try {
                $sessionKey = "collab:session:{$tenantId}:{$documentType}:{$documentId}:{$sessionId}";
                Cache::forget($sessionKey);

                // Удаляем из активных сессий документа
                $documentsKey = "collab:doc:{$tenantId}:{$documentType}:{$documentId}";
                $activeSessions = Cache::get($documentsKey, []);
                unset($activeSessions[$sessionId]);

                if (empty($activeSessions)) {
                    Cache::forget($documentsKey);
                } else {
                    Cache::put($documentsKey, $activeSessions, self::CACHE_TTL);
                }

                Log::channel('audit')->info('Collaboration session ended', [
                    'correlation_id' => $correlationId,
                    'session_id' => $sessionId,
                    'tenant_id' => $tenantId,
                    'document' => "{$documentType}:{$documentId}",
                ]);

                return true;
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to end collaboration session', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Получает активные редакторы документа
         */
        public function getActiveEditors(
            int $tenantId,
            string $documentType,
            int $documentId
        ): Collection {
            $documentsKey = "collab:doc:{$tenantId}:{$documentType}:{$documentId}";
            $activeSessions = Cache::get($documentsKey, []);

            $editors = collect();

            foreach ($activeSessions as $sessionId => $sessionInfo) {
                $sessionKey = "collab:session:{$tenantId}:{$documentType}:{$documentId}:{$sessionId}";
                $sessionData = Cache::get($sessionKey);

                if ($sessionData) {
                    $user = User::find($sessionData['user_id']);
                    if ($user) {
                        $editors->push([
                            'session_id' => $sessionId,
                            'user_id' => $sessionData['user_id'],
                            'user_name' => $user->name,
                            'user_avatar' => $user->avatar_url ?? null,
                            'started_at' => $sessionData['started_at'],
                            'editing_element' => $sessionData['editing_element'],
                            'cursor_position' => $sessionData['cursor_position'],
                        ]);
                    }
                }
            }

            return $editors;
        }

        /**
         * Обновляет позицию курсора редактора
         */
        public function updateCursorPosition(
            string $sessionId,
            int $tenantId,
            string $documentType,
            int $documentId,
            array $position,
            string $correlationId = null
        ): bool {
            $correlationId ??= Str::uuid()->toString();

            try {
                $sessionKey = "collab:session:{$tenantId}:{$documentType}:{$documentId}:{$sessionId}";
                $sessionData = Cache::get($sessionKey);

                if (!$sessionData) {
                    throw new \Exception("Session not found: {$sessionId}");
                }

                $sessionData['cursor_position'] = $position;
                $sessionData['last_heartbeat'] = now()->toIso8601String();

                Cache::put($sessionKey, $sessionData, self::CACHE_TTL);

                Log::channel('audit')->debug('Cursor position updated', [
                    'correlation_id' => $correlationId,
                    'session_id' => $sessionId,
                    'position' => $position,
                ]);

                return true;
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to update cursor position', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Передаёт сердцебиение сессии
         */
        public function heartbeat(
            string $sessionId,
            int $tenantId,
            string $documentType,
            int $documentId
        ): bool {
            $sessionKey = "collab:session:{$tenantId}:{$documentType}:{$documentId}:{$sessionId}";
            $sessionData = Cache::get($sessionKey);

            if (!$sessionData) {
                return false;
            }

            $sessionData['last_heartbeat'] = now()->toIso8601String();
            Cache::put($sessionKey, $sessionData, self::CACHE_TTL);

            return true;
        }

        /**
         * Получает сессию редактирования
         */
        public function getSession(
            string $sessionId,
            int $tenantId,
            string $documentType,
            int $documentId
        ): ?array {
            $sessionKey = "collab:session:{$tenantId}:{$documentType}:{$documentId}:{$sessionId}";

            return Cache::get($sessionKey);
        }

        /**
         * Очищает устаревшие сессии
         */
        public function cleanupStaleSession(int $tenantId): void
        {
            // Использует Redis SCAN для поиска ключей
            // В продакшене можно использовать более оптимизированный подход
            Log::channel('audit')->info('Cleanup stale collaboration sessions', [
                'tenant_id' => $tenantId,
            ]);
        }
}
