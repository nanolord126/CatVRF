<?php declare(strict_types=1);

namespace App\Services;

use App\Models\User;



use Illuminate\Support\Str;
use App\Services\FraudControlService;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class NotificationPreferencesService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly FraudControlService $fraud,
    ) {}


    /**
         * Get user notification preferences
         * @param int $userId
         * @return array
         */
        public function getPreferences(int $userId): array
        {
            $correlationId = Str::uuid()->toString();

            try {
                $user = User::findOrFail($userId);

                return [
                    'user_id' => $userId,
                    'email' => $this->getChannelPreferences($userId, 'email'),
                    'sms' => $this->getChannelPreferences($userId, 'sms'),
                    'push' => $this->getChannelPreferences($userId, 'push'),
                    'in_app' => $this->getChannelPreferences($userId, 'in_app'),
                    'do_not_disturb' => [
                        'enabled' => cache()->get("dnd:user.{$userId}.enabled", false),
                        'start_time' => cache()->get("dnd:user.{$userId}.start_time"),
                        'end_time' => cache()->get("dnd:user.{$userId}.end_time"),
                    ],
                    'correlation_id' => $correlationId,
                ];
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to get notification preferences', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }
        }

        /**
         * Get preferences for specific channel
         * @param int $userId
         * @param string $channel
         * @return array
         */
        private function getChannelPreferences(int $userId, string $channel): array
        {
            return [
                'enabled' => cache()->get("notif:channel:{$userId}:{$channel}:enabled", true),
                'categories' => cache()->get("notif:channel:{$userId}:{$channel}:categories", [
                    'orders' => true,
                    'payments' => true,
                    'promotions' => true,
                    'system' => true,
                ]),
            ];
        }

        /**
         * Update channel preferences
         * @param int $userId
         * @param string $channel
         * @param array $preferences
         * @return bool
         */
        public function updateChannelPreferences(
            int $userId,
            string $channel,
            array $preferences
        ): bool {
            $correlationId = Str::uuid()->toString();

            try {
                $this->fraud->check(
                    userId: $userId,
                    operationType: 'notification_preferences_update',
                    amount: 0,
                    correlationId: $correlationId,
                );
                $this->db->transaction(function () use ($userId, $channel, $preferences, $correlationId) {
                    $this->db->table('notification_preferences')
                        ->upsert(
                            [
                                'user_id'            => $userId,
                                'notification_type'  => $preferences['notification_type'] ?? 'general',
                                'channel'            => $channel,
                                'is_enabled'         => $preferences['enabled'] ?? true,
                                'quiet_from'         => $preferences['quiet_from'] ?? null,
                                'quiet_to'           => $preferences['quiet_to'] ?? null,
                                'frequency'          => $preferences['frequency'] ?? 'instant',
                                'correlation_id'     => $correlationId,
                                'created_at'         => now(),
                                'updated_at'         => now(),
                            ],
                            ['user_id', 'notification_type', 'channel'],
                            ['is_enabled', 'quiet_from', 'quiet_to', 'frequency', 'correlation_id', 'updated_at']
                        );

                    // cache invalidation
                    cache()->forget("notif:prefs:{$userId}:{$channel}");
                });

                $this->logger->channel('audit')->info('Notification preferences updated', [
                    'user_id' => $userId,
                    'channel' => $channel,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to update preferences', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return false;
            }
        }

        /**
         * Enable do-not-disturb mode
         * @param int $userId
         * @param string $startTime (HH:mm)
         * @param string $endTime (HH:mm)
         * @return bool
         */
        public function setDoNotDisturb(int $userId, string $startTime, string $endTime): bool
        {
            try {
                cache()->put("dnd:user.{$userId}.enabled", true, 86400 * 365);
                cache()->put("dnd:user.{$userId}.start_time", $startTime, 86400 * 365);
                cache()->put("dnd:user.{$userId}.end_time", $endTime, 86400 * 365);

                $this->logger->channel('audit')->info('Do-not-disturb enabled', [
                    'user_id' => $userId,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]);

                return true;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to set do-not-disturb', [
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        /**
         * Disable do-not-disturb mode
         * @param int $userId
         * @return bool
         */
        public function disableDoNotDisturb(int $userId): bool
        {
            try {
                cache()->forget("dnd:user.{$userId}.enabled");
                cache()->forget("dnd:user.{$userId}.start_time");
                cache()->forget("dnd:user.{$userId}.end_time");

                $this->logger->channel('audit')->info('Do-not-disturb disabled', [
                    'user_id' => $userId,
                ]);

                return true;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to disable do-not-disturb', [
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }
}
