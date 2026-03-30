<?php declare(strict_types=1);

namespace App\Domains\Common\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UpdateUserTasteProfileListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public int $tries = 3;

        public int $timeout = 30;

        public function __construct(
            private readonly UserTasteProfileService $tasteService,
        ) {}

        public function handle(UserInteractionEvent $event): void
        {
            try {
                // 1. Обновить базовый профиль (timestamp, счётчик взаимодействий)
                $this->tasteService->updateProfileFromInteraction(
                    $event->userId,
                    $event->tenantId,
                    $event->interactionType,
                    $event->data,
                    $event->correlationId,
                );

                // 2. Обновить специфические данные на основе типа взаимодействия
                DB::transaction(function () use ($event) {
                    $profile = UserTasteProfile::where([
                        'user_id' => $event->userId,
                        'tenant_id' => $event->tenantId,
                    ])->lockForUpdate()->first();

                    if (!$profile) {
                        return;
                    }

                    // Получить текущую историю
                    $history = $profile->interaction_history ?? [];

                    // Добавить новое взаимодействие (максимум 100 последних)
                    $newInteraction = [
                        'type' => $event->interactionType,
                        'product_id' => $event->data['product_id'] ?? null,
                        'vertical' => $event->data['vertical'] ?? null,
                        'category' => $event->data['category'] ?? null,
                        'price' => $event->data['price'] ?? null,
                        'rating' => $event->data['rating'] ?? null,
                        'created_at' => now()->toIso8601String(),
                    ];

                    $history[] = $newInteraction;
                    $history = array_slice($history, -100); // Хранить последние 100

                    // Обновить неявные scores на основе типа взаимодействия
                    $implicitScore = $profile->implicit_score ?? [];

                    if ($vertical = $event->data['vertical'] ?? null) {
                        // Увеличить score для категории в зависимости от типа взаимодействия
                        $weightByType = [
                            'view' => 0.1,
                            'cart_add' => 0.3,
                            'cart_remove' => -0.2,
                            'purchase' => 0.8,
                            'review' => 0.5,
                            'rating' => 0.4,
                            'like' => 0.2,
                            'wishlist_add' => 0.4,
                        ];

                        $weight = $weightByType[$event->interactionType] ?? 0.1;
                        $implicitScore[$vertical] = min(
                            1.0,
                            ($implicitScore[$vertical] ?? 0.1) + $weight * 0.1
                        );
                    }

                    // Сохранить обновления
                    $profile->update([
                        'interaction_history' => $history,
                        'implicit_score' => $implicitScore,
                    ]);
                });

                Log::channel('audit')->info('User taste profile updated from interaction', [
                    'user_id' => $event->userId,
                    'interaction_type' => $event->interactionType,
                    'correlation_id' => $event->correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to update taste profile from interaction', [
                    'user_id' => $event->userId,
                    'interaction_type' => $event->interactionType,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);

                // Не переhrowить, чтобы не повторять бесконечно
            }
        }

        /**
         * Что делать если listener не удалось обработать
         */
        public function failed(UserInteractionEvent $event, \Throwable $exception): void
        {
            Log::channel('audit')->error('UserTasteProfileListener failed permanently', [
                'user_id' => $event->userId,
                'interaction_type' => $event->interactionType,
                'exception' => $exception->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }
}
