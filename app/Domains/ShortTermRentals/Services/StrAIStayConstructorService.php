<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StrAIStayConstructorService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly AIConstructorService $aiService,
            private readonly StrAvailabilityService $availabilityService,
        ) {}

        /**
         * Подбор апартаментов по фото "мечты" пользователя или описанию
         */
        public function proposeApartsByInspo(int $userId, string $inspoText, ?string $photoPath = null): Collection
        {
            $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

            // 1. Анализируем вкус пользователя (UserTasteProfile - симуляция профиля)
            // В реальном проекте используем: $profile = $user->taste_profile;

            // 2. Вызов AI Constructor Service (общий фреймворк)
            // $aiRecommendation = $this->aiService->analyzeAndRecommend($photoPath, 'ShortTermRentals', $userId);

            // Симуляция AI выдачи на базе КАНОНа 2026
            $suggestedTypes = ['loft', 'studio'];
            if (str_contains(strtolower($inspoText), 'villa')) {
                $suggestedTypes[] = 'villa';
            }

            // 3. Поиск апартаментов в базе
            $aparts = StrApartment::whereHas('property', function ($query) use ($suggestedTypes) {
                $query->whereIn('type', $suggestedTypes);
            })
            ->where('is_available', true)
            ->limit(10)
            ->get();

            // 4. Фильтрация по реальной доступности (CalendarAvailability)
            $availableAparts = $aparts->filter(function ($apart) {
                return $this->availabilityService->isAvailable(
                    $apart->id,
                    now()->addDays(7)->startOfDay(),
                    now()->addDays(10)->startOfDay()
                );
            });

            // 5. Логирование использования AI
            Log::channel('audit')->info('AI Stay Constructor Propose', [
                'user_id' => $userId,
                'inspo' => $inspoText,
                'suggested_count' => $availableAparts->count(),
                'correlation_id' => $correlationId,
            ]);

            return $availableAparts;
        }

        /**
         * Создание "Виртуальной примерки" (AI-превью интерьера под клиента)
         */
        public function generateVirtualPreview(int $apartmentId, int $userId): array
        {
            $apartment = StrApartment::findOrFail($apartmentId);

            Log::channel('audit')->info('AI Virtual Stay Preview Generated', [
                'apartment_id' => $apartmentId,
                'user_id' => $userId,
                'correlation_id' => (string) Str::uuid(),
            ]);

            return [
                'success' => true,
                'preview_url' => "https://cdn.catvrf.io/ai/previews/{$apartment->uuid}/v1.jpg",
                'is_dynamic' => true,
            ];
        }
}
