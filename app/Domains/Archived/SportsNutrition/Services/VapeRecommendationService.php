<?php declare(strict_types=1);

namespace App\Domains\Archived\SportsNutrition\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VapeRecommendationService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**


         * Конструктор с DP зависимостью (RecommendationService).


         */


        public function __construct(


            private RecommendationService $recommendation,


        ) {}


        /**


         * Получить персонализированные рекомендации для пользователя.


         *


         * @param int $userId ID пользователя


         * @param string $flavorProfile Профиль вкусов


         */


        public function getRecommendationsForUser(int $userId, string $flavorProfile = null, string $correlationId = null): Collection


        {


            $correlationId ??= (string) Str::uuid();


            Log::channel('audit')->info('Vape recommendations: get for user', [


                'user_id' => $userId,


                'flavor_profile' => $flavorProfile,


                'correlation_id' => $correlationId,


            ]);


            // 1. Возвращает рекомендации через общую систему (включая AI и Embeddings)


            return $this->recommendation->getForUser(


                userId: $userId,


                vertical: 'vapes',


                context: [


                    'flavor_profile' => $flavorProfile,


                    'correlation_id' => $correlationId,


                ]


            );


        }


        /**


         * Кросс-рекомендации после покупки.


         * Например, после покупки жидкости (VapeLiquid) — рекомендовать новое POD-устройство.


         */


        public function getCrossRecommendations(int $userId, string $currentProductType, string $correlationId = null): Collection


        {


            $correlationId ??= (string) Str::uuid();


            Log::channel('audit')->info('Vape cross-recommendations: get', [


                'user_id' => $userId,


                'current_product_type' => $currentProductType,


                'correlation_id' => $correlationId,


            ]);


            $vertical = $currentProductType === 'liquid' ? 'vapes_devices' : 'vapes_liquids';


            return $this->recommendation->getCrossVertical(


                userId: $userId,


                currentVertical: $vertical,


            );


        }
}
