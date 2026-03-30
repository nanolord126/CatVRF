<?php declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIConstructorService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private ImageAnalysisService $imageAnalysis,
            private InteriorConstructor $interiorConstructor,
            private BeautyLookConstructor $beautyLookConstructor,
            private OutfitConstructor $outfitConstructor,
            private CakeConstructor $cakeConstructor,
            private MenuConstructor $menuConstructor,
            private \App\Services\FraudControlService $fraudControl,
            private \App\Services\InventoryManagementService $inventory,
            private \App\Services\RecommendationService $recommendation,
            private \App\Services\WalletService $wallet,
        ) {}

        /**
         * Запустить конструктор (основная точка входа)
         *
         * @param User $user Пользователь
         * @param string $type Тип конструктора (interior, beauty_look, outfit, cake, menu)
         * @param UploadedFile $photo Загруженное фото
         * @param array $params Дополнительные параметры
         * @return array {success: bool, construction: AIConstruction, result: array, taste_used: array, confidence: float}
         */
        public function run(
            User $user,
            string $type,
            UploadedFile $photo,
            array $params = [],
        ): array {
            $correlationId = Str::uuid()->toString();

            return DB::transaction(function () use ($user, $type, $photo, $params, $correlationId) {
                try {
                    // 1. Fraud check
                    $this->fraudControl->check([
                        'user_id' => $user->id,
                        'action' => "ai_constructor_{$type}",
                        'ip' => \request()->ip(),
                    ]);

                    Log::channel('audit')->info("AI Constructor started: {$type}", [
                        'correlation_id' => $correlationId,
                        'user_id' => $user->id,
                        'type' => $type,
                    ]);

                    // 2. Получить или создать профиль вкусов
                    $tasteProfile = $this->getOrCreateTasteProfile($user);

                    // 3. Сохранить фото
                    $photoPath = $this->imageAnalysis->storePhoto($photo, $type);

                    // 4. Анализировать фото
                    $analysis = $this->imageAnalysis->analyze($photo, $params['prompt'] ?? '', [
                        'vertical' => $type,
                    ]);

                    // 5. Получить используемые вкусы
                    [$explicitPrefs, $implicitPrefs] = $this->extractUsedPreferences(
                        $tasteProfile,
                        $type,
                        $analysis,
                        $params,
                    );

                    // 6. Запустить конкретный конструктор
                    $constructorResult = $this->runConstructor(
                        $type,
                        $analysis,
                        $explicitPrefs,
                        $implicitPrefs,
                        $params,
                    );

                    // 7. Сохранить результаты в БД
                    $construction = AIConstruction::create([
                        'uuid' => Str::uuid(),
                        'user_id' => $user->id,
                        'tenant_id' => $user->current_tenant_id,
                        'type' => $type,
                        'correlation_id' => $correlationId,
                        'input_data' => [
                            'params' => $params,
                            'photo_size' => $photo->getSize(),
                            'photo_mime' => $photo->getMimeType(),
                        ],
                        'photo_path' => $photoPath,
                        'analysis_result' => $analysis,
                        'construction_data' => $constructorResult['data'],
                        'recommended_items' => $constructorResult['items'],
                        'taste_profile_used' => $tasteProfile->getAllPreferences(),
                        'explicit_preferences_used' => $explicitPrefs,
                        'implicit_preferences_used' => $implicitPrefs,
                        'confidence_score' => (float)($constructorResult['confidence'] ?? 0.5),
                        'confidence_breakdown' => $constructorResult['confidence_breakdown'] ?? [],
                    ]);

                    Log::channel('audit')->info("AI Constructor completed successfully", [
                        'correlation_id' => $correlationId,
                        'user_id' => $user->id,
                        'type' => $type,
                        'construction_id' => $construction->id,
                        'confidence' => $construction->confidence_score,
                        'items_count' => \count($construction->recommended_items ?? []),
                    ]);

                    return [
                        'success' => true,
                        'correlation_id' => $correlationId,
                        'construction' => $construction,
                        'result' => $constructorResult,
                        'taste_used' => [
                            'explicit' => $explicitPrefs,
                            'implicit' => $implicitPrefs,
                        ],
                        'confidence' => $construction->confidence_score,
                    ];
                } catch (\Throwable $e) {
                    Log::channel('audit')->error("AI Constructor failed", [
                        'correlation_id' => $correlationId,
                        'user_id' => $user->id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    throw $e;
                }
            });
        }

        /**
         * Получить или создать профиль вкусов пользователя
         */
        private function getOrCreateTasteProfile(User $user): UserTasteProfile
        {
            return $user->tasteProfile()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'uuid' => Str::uuid(),
                    'tenant_id' => $user->current_tenant_id,
                    'version' => '2.0',
                    'is_active' => true,
                ]
            );
        }

        /**
         * Извлечь используемые явные и неявные предпочтения
         */
        private function extractUsedPreferences(
            UserTasteProfile $tasteProfile,
            string $type,
            array $analysis,
            array $params,
        ): array {
            $explicit = [];
            $implicit = [];

            // Явные предпочтения из параметров
            if (isset($params['explicit_preferences'])) {
                $explicit = $params['explicit_preferences'];
            }

            // Явные предпочтения из профиля
            $profileExplicit = $tasteProfile->getPreferencesForConstructor($type);
            $explicit = \array_merge($explicit, $profileExplicit);

            // Неявные предпочтения из анализа фото
            $implicit = [
                'detected_colors' => $analysis['colors'] ?? [],
                'detected_styles' => $analysis['styles'] ?? [],
                'detected_elements' => $analysis['elements'] ?? [],
                'analysis_confidence' => $analysis['confidence'] ?? 0.5,
            ];

            // Добавить неявные предпочтения из профиля
            if ($tasteProfile->implicit_preferences) {
                $implicit = \array_merge($implicit, $tasteProfile->implicit_preferences);
            }

            return [$explicit, $implicit];
        }

        /**
         * Запустить конкретный конструктор по типу
         */
        private function runConstructor(
            string $type,
            array $analysis,
            array $explicit,
            array $implicit,
            array $params,
        ): array {
            return match ($type) {
                'interior' => $this->interiorConstructor->construct($analysis, $explicit, $implicit, $params),
                'beauty_look' => $this->beautyLookConstructor->construct($analysis, $explicit, $implicit, $params),
                'outfit' => $this->outfitConstructor->construct($analysis, $explicit, $implicit, $params),
                'cake' => $this->cakeConstructor->construct($analysis, $explicit, $implicit, $params),
                'menu' => $this->menuConstructor->construct($analysis, $explicit, $implicit, $params),
                default => throw new \Exception("Unknown constructor type: {$type}"),
            };
        }

        /**
         * Получить сохранённые конструкции пользователя
         */
        public function getSavedConstructions(User $user, ?string $type = null, int $limit = 20): array
        {
            $query = AIConstruction::where('user_id', $user->id)
                ->where('saved', true)
                ->orderByDesc('saved_at');

            if ($type) {
                $query->where('type', $type);
            }

            return $query->limit($limit)
                ->get()
                ->map(fn (AIConstruction $c) => [
                    'id' => $c->id,
                    'uuid' => $c->uuid,
                    'type' => $c->type,
                    'photo_url' => $c->getPhotoUrl(),
                    'confidence' => $c->confidence_score,
                    'items_count' => \count($c->recommended_items ?? []),
                    'saved_at' => $c->saved_at,
                    'rating' => $c->rating,
                    'purchases' => $c->items_purchased,
                    'conversion_rate' => $c->getConversionRate(),
                ])
                ->toArray();
        }

        /**
         * Получить статистику конструкций
         */
        public function getStatistics(User $user): array
        {
            $constructions = AIConstruction::where('user_id', $user->id)->get();

            return [
                'total_constructions' => $constructions->count(),
                'by_type' => $constructions->groupBy('type')->map->count()->toArray(),
                'avg_confidence' => (float)$constructions->avg('confidence_score'),
                'saved_count' => $constructions->where('saved', true)->count(),
                'with_ratings' => $constructions->whereNotNull('rating')->count(),
                'avg_rating' => (float)$constructions->whereNotNull('rating')->avg('rating'),
                'total_purchases' => (int)$constructions->sum('items_purchased'),
                'total_spent' => (int)$constructions->sum('purchase_total'),
                'avg_conversion' => (float)$constructions
                    ->filter(fn (AIConstruction $c) => $c->items_added_to_cart > 0)
                    ->avg(fn (AIConstruction $c) => $c->getConversionRate()),
            ];
        }

        /**
         * Удалить конструкцию
         */
        public function deleteConstruction(AIConstruction $construction): void
        {
            $construction->deletePhoto();
            $construction->delete();

            Log::channel('audit')->info("AI Construction deleted", [
                'correlation_id' => $construction->correlation_id,
                'construction_id' => $construction->id,
            ]);
        }
}
