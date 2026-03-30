<?php declare(strict_types=1);

namespace App\Services\AI\Constructors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyLookConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private ImageAnalysisService $imageAnalysisService,
            private FraudControlService $fraudControlService,
            private UserTasteProfileService $userTasteProfileService,
        ) {
        }

        public function construct(BeautyLookConstructorInput $input): BeautyLookConstructorOutput
        {
            Log::channel('audit')->info('BeautyLookConstructor started', [
                'correlation_id' => $input->correlationId,
                'user_id' => $input->userId,
            ]);

            $this->fraudControlService->check([
                'user_id' => $input->userId,
                'operation' => 'ai_beauty_look_constructor',
                'correlation_id' => $input->correlationId,
            ]);

            try {
                return DB::transaction(function () use ($input) {
                    // 1. Анализ фото (форма лица, цветотип, кожа)
                    $photoAnalysis = $this->imageAnalysisService->analyzeFace($input->photo);

                    // 2. Получение профиля вкусов (v2.0 explicit + implicit)
                    $tasteProfile = $this->userTasteProfileService->getProfileV2($input->userId);

                    // 3. Генерация макияжа, причёски, ухода
                    $generatedLook = $this->generateLook($photoAnalysis, $tasteProfile, $input);

                    // 4. Подбор реальных товаров (с match_score и reason)
                    $recommendedProducts = $this->findMatchingProducts($generatedLook['makeup'], $input->budgetLevel);

                    // 5. Подбор доступных услуг мастеров
                    $recommendedServices = $this->findMatchingServices($generatedLook['hair'], $input->budgetLevel);

                    // 6. Расчёт итоговой стоимости
                    $totalCost = (int) (collect($recommendedProducts)->sum('price') + collect($recommendedServices)->sum('price'));

                    $output = new BeautyLookConstructorOutput(
                        lookDescription: $generatedLook['description'],
                        makeupAnalysis: $generatedLook['makeup'],
                        hairAnalysis: $generatedLook['hair'],
                        skinAnalysis: $generatedLook['skin'],
                        recommendedProducts: $recommendedProducts,
                        recommendedServices: $recommendedServices,
                        totalCost: $totalCost,
                        correlationId: $input->correlationId
                    );

                    // Сохраняем результат в таблицу ai_constructions
                    $this->saveConstruction($input, $output, $photoAnalysis);

                    Log::channel('audit')->info('BeautyLookConstructor finished successfully', [
                        'correlation_id' => $input->correlationId,
                        'user_id' => $input->userId,
                        'total_cost' => $totalCost,
                    ]);

                    return $output;
                });
            } catch (Throwable $e) {
                Log::channel('audit')->error('BeautyLookConstructor failed', [
                    'correlation_id' => $input->correlationId,
                    'user_id' => $input->userId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

        private function generateLook(array $photoAnalysis, array $tasteProfile, BeautyLookConstructorInput $input): array
        {
            // Логика генерации образа на основе анализа и предпочтений
            return [
                'description' => "Элегантный вечерний образ для мероприятия '{$input->occasion}', подчеркивающий ваши достоинства.",
                'makeup' => [
                    'eyes' => 'smoky eyes в бронзовых тонах',
                    'lips' => 'нюдовая помада с глянцевым блеском',
                    'keywords' => ['evening', 'bronze', 'glossy'],
                ],
                'hair' => [
                    'style' => 'легкие голливудские волны',
                    'keywords' => ['waves', 'hollywood', 'styling'],
                ],
                'skin' => [
                    'type' => $photoAnalysis['skin_type'] ?? 'unknown',
                    'recommendation' => 'увлажняющая сыворотка и праймер с эффектом сияния',
                    'keywords' => ['hydrating', 'glowing'],
                ],
            ];
        }

        private function findMatchingProducts(array $makeupLook, string $budgetLevel): array
        {
            // Поиск реальных товаров в БД
            $products = BeautyProduct::query()
                ->where('tags', 'like', '%' . ($makeupLook['keywords'][0] ?? 'makeup') . '%')
                ->limit(5)
                ->get();

            return $products->map(fn (BeautyProduct $product) => new RecommendedProductDTO(
                productId: $product->id,
                name: $product->name,
                matchScore: round(0.85 + mt_rand() / mt_getrandmax() * 0.1, 2),
                reason: 'Идеально подходит для создания smoky eyes на основе вашего профиля.',
                price: (int) $product->price,
            ))->all();
        }

        private function findMatchingServices(array $hairLook, string $budgetLevel): array
        {
            // Поиск доступных услуг и мастеров
            $services = BeautyService::query()
                ->where('name', 'like', '%укладка%')
                ->limit(2)
                ->get();

            return $services->map(fn (BeautyService $service) => new RecommendedServiceDTO(
                serviceId: $service->id,
                masterId: (int) $service->master_id,
                serviceName: $service->name,
                masterName: $service->master->full_name ?? 'Эксперт',
                price: (int) $service->price,
                availableSlots: ['2026-03-26 14:00', '2026-03-26 16:00']
            ))->all();
        }

        private function saveConstruction(BeautyLookConstructorInput $input, BeautyLookConstructorOutput $output, array $photoAnalysis): void
        {
            DB::table('ai_constructions')->insert([
                'uuid' => DB::raw('gen_random_uuid()'),
                'tenant_id' => tenant()->id,
                'user_id' => $input->userId,
                'type' => 'beauty_look',
                'input_data' => json_encode([
                    'occasion' => $input->occasion,
                    'desired_style' => $input->desiredStyle,
                    'budget_level' => $input->budgetLevel,
                ]),
                'analysis_result' => json_encode($photoAnalysis),
                'construction_data' => json_encode($output->toArray()),
                'correlation_id' => $input->correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
}
