<?php declare(strict_types=1);

namespace App\Domains\Hotels\Services\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIStayConstructorService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly RecommendationService $recommendation,
            private string $correlationId = '',
        ) {
            $this->correlationId = $this->correlationId ?: (string) Str::uuid();
        }

        /**
         * Создать персонализированный план проживания.
         *
         * @param int $userId ID пользователя (UserTasteProfile)
         * @param int $hotelId Выбранный отель
         * @param array $preferences Пожелания (текст/фильтры)
         */
        public function constructStay(int $userId, int $hotelId, array $preferences): array
        {
            Log::channel('audit')->info('AI Stay Constructor Started', [
                'user_id' => $userId,
                'hotel_id' => $hotelId,
                'preferences' => $preferences,
                'correlation_id' => $this->correlationId,
            ]);

            $hotel = Hotel::findOrFail($hotelId);

            // 1. ПОИСК НОМЕРОВ С УЧЕТОМ ВКУСОВ (RecommendationService)
            $recommendedRooms = $this->recommendation->getForUser(
                userId: $userId,
                vertical: 'hotels',
                context: [
                    'hotel_id' => $hotelId,
                    'preferences' => $preferences,
                ]
            );

            // 2. ФОРМИРОВАНИЕ ПЛАНА (AI Logic Placeholder - OpenAI/Foundry)
            $aiPlan = [
                'suggestion' => "На базе ваших предпочтений ({$preferences['text']}), рекомендуем {$hotel->name}. " .
                               "Этот отель идеально подходит для вашего стиля путешествий.",
                'itinerary' => [
                    'day1' => 'Check-in, приветственный ужин',
                    'day2' => 'Посещение спа-комплекса, отдых у бассейна',
                    'day3' => 'Check-out, завтрак с видом на город',
                ],
                'rooms' => $recommendedRooms->map(fn($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'price' => $r->price,
                ])->toArray(),
            ];

            Log::channel('audit')->info('AI Stay Constructor Completed', [
                'correlation_id' => $this->correlationId,
                'confidence_score' => 0.95,
            ]);

            return $aiPlan;
        }

        public function getCorrelationId(): string
        {
            return $this->correlationId;
        }
}
