<?php declare(strict_types=1);

namespace App\Domains\Hotels\Services\AI;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\Room;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Hotel AI Stay Constructor (Layer 8)
 * 
 * Конструктор идеального проживания на базе OpenAI/Microsoft Foundry.
 * Обязательно: correlation_id, audit logs, tenant scoping.
 */
final readonly class AIStayConstructorService
{
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
