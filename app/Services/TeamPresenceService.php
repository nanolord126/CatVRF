<?php declare(strict_types=1);

namespace App\Services;



use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

final readonly class TeamPresenceService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
    ) {}

    private const PRESENCE_TTL = 600; // 10 minutes

        /**
         * Регистрирует присутствие пользователя в документе
         */
        public function registerPresence(
            int $userId,
            int $tenantId,
            string $documentType,
            int $documentId,
            ?string $correlationId = null
        ): array {
            $correlationId ??= Str::uuid()->toString();

            try {
                $presenceKey = "collab:presence:{$tenantId}:{$documentType}:{$documentId}:{$userId}";

                $presence = [
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                    'joined_at' => now()->toIso8601String(),
                    'last_active' => now()->toIso8601String(),
                    'status' => 'active', // active, idle, away
                    'color' => $this->getUserColor($userId),
                    'correlation_id' => $correlationId,
                ];

                $this->cache->put($presenceKey, $presence, self::PRESENCE_TTL);

                // Добавляем в список присутствующих
                $documentPresenceKey = "collab:present:{$tenantId}:{$documentType}:{$documentId}";
                $presentUsers = $this->cache->get($documentPresenceKey, []);
                $presentUsers[$userId] = now()->toIso8601String();
                $this->cache->put($documentPresenceKey, $presentUsers, self::PRESENCE_TTL);

                $this->logger->channel('audit')->debug('User presence registered', [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId,
                    'document' => "{$documentType}:{$documentId}",
                ]);

                return $presence;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to register presence', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Удаляет присутствие пользователя
         */
        public function unregisterPresence(
            int $userId,
            int $tenantId,
            string $documentType,
            int $documentId,
            ?string $correlationId = null
        ): bool {
            $correlationId ??= Str::uuid()->toString();

            try {
                $presenceKey = "collab:presence:{$tenantId}:{$documentType}:{$documentId}:{$userId}";
                $this->cache->forget($presenceKey);

                $documentPresenceKey = "collab:present:{$tenantId}:{$documentType}:{$documentId}";
                $presentUsers = $this->cache->get($documentPresenceKey, []);
                unset($presentUsers[$userId]);

                if (empty($presentUsers)) {
                    $this->cache->forget($documentPresenceKey);
                } else {
                    $this->cache->put($documentPresenceKey, $presentUsers, self::PRESENCE_TTL);
                }

                $this->logger->channel('audit')->debug('User presence unregistered', [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId,
                ]);

                return true;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to unregister presence', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Обновляет статус пользователя
         */
        public function updateStatus(
            int $userId,
            int $tenantId,
            string $documentType,
            int $documentId,
            string $status,
            ?string $correlationId = null
        ): bool {
            $correlationId ??= Str::uuid()->toString();

            try {
                $presenceKey = "collab:presence:{$tenantId}:{$documentType}:{$documentId}:{$userId}";
                $presence = $this->cache->get($presenceKey);

                if (!$presence) {
                    throw new \RuntimeException("Presence not found for user {$userId}");
                }

                $presence['status'] = $status;
                $presence['last_active'] = now()->toIso8601String();

                $this->cache->put($presenceKey, $presence, self::PRESENCE_TTL);

                return true;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to update status', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Получает список присутствующих пользователей
         */
        public function getPresenceList(
            int $tenantId,
            string $documentType,
            int $documentId
        ): array {
            $documentPresenceKey = "collab:present:{$tenantId}:{$documentType}:{$documentId}";
            $presentUsers = $this->cache->get($documentPresenceKey, []);

            $presence = [];

            foreach ($presentUsers as $userId => $timestamp) {
                $presenceKey = "collab:presence:{$tenantId}:{$documentType}:{$documentId}:{$userId}";
                $userPresence = $this->cache->get($presenceKey);

                if ($userPresence) {
                    $presence[] = $userPresence;
                }
            }

            return $presence;
        }

        /**
         * Получает количество присутствующих
         */
        public function getPresenceCount(
            int $tenantId,
            string $documentType,
            int $documentId
        ): int {
            $documentPresenceKey = "collab:present:{$tenantId}:{$documentType}:{$documentId}";
            $presentUsers = $this->cache->get($documentPresenceKey, []);

            return count($presentUsers);
        }

        /**
         * Генерирует цвет для пользователя (для визуализации курсора)
         */
        private function getUserColor(int $userId): string
        {
            $colors = [
                '#FF6B6B', // Red
                '#4ECDC4', // Teal
                '#45B7D1', // Blue
                '#FFA07A', // Light Salmon
                '#98D8C8', // Mint
                '#F7DC6F', // Yellow
                '#BB8FCE', // Purple
                '#85C1E2', // Sky Blue
            ];

            return $colors[$userId % count($colors)];
        }
}
