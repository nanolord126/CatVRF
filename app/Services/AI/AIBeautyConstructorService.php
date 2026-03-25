<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\UserBodyMetrics;
use App\Models\UserTasteProfile;
use App\Services\ML\UserTasteProfileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AI Beauty Constructor Service
 * Анализирует фото лица + вкусы пользователя → рекомендует макияж/причёску/уход
 * CANON 2026: Для вертикали Beauty
 */
final readonly class AIBeautyConstructorService
{
    public function __construct(
        private readonly UserTasteProfileService $tasteService,
        private readonly \OpenAI\Client $openai,
    ) {}

    /**
     * Анализировать фото и получить рекомендации красоты
     *
     * @param UploadedFile $facePhoto Фото лица пользователя
     * @param int $userId Пользователь
     * @param int $tenantId Тенант
     * @return array Рекомендации: причёски, макияж, уход
     */
    public function analyzeFaceAndRecommend(
        UploadedFile $facePhoto,
        int $userId,
        int $tenantId,
        ?string $correlationId = null
    ): array {
        $correlationId ??= \Illuminate\Support\Str::uuid()->toString();

        try {
            Log::channel('audit')->info('Beauty constructor: Starting face analysis', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);

            // 1. Получить профиль вкусов пользователя
            $tasteProfile = $this->tasteService->getOrCreateProfile($userId, $tenantId, $correlationId);
            $bodyMetrics = UserBodyMetrics::where('user_id', $userId)->first();

            // 2. Анализировать фото лица через Vision API
            $faceAnalysis = $this->analyzeFacePhoto($facePhoto);

            if (!$faceAnalysis) {
                throw new \Exception('Failed to analyze face photo');
            }

            // 3. Объединить данные: физические параметры + вкусы + анализ лица
            $userProfile = $this->buildBeautyProfile($tasteProfile, $bodyMetrics, $faceAnalysis);

            // 4. Получить рекомендации
            $recommendations = [
                'hairstyles' => $this->recommendHairstyles($userProfile),
                'makeup' => $this->recommendMakeup($userProfile),
                'skincare' => $this->recommendSkincare($userProfile),
                'colors' => $this->recommendColors($userProfile),
            ];

            // 5. Логировать
            Log::channel('audit')->info('Beauty constructor: Analysis complete', [
                'user_id' => $userId,
                'recommendations_count' => array_sum(array_map('count', $recommendations)),
                'correlation_id' => $correlationId,
            ]);

            // 6. Записать взаимодействие
            $this->tasteService->recordInteraction(
                $userId,
                $tenantId,
                'beauty_constructor_used',
                [
                    'face_analysis' => $faceAnalysis,
                    'recommendations_count' => count($recommendations),
                ],
                $correlationId
            );

            return [
                'success' => true,
                'face_analysis' => $faceAnalysis,
                'recommendations' => $recommendations,
                'user_profile' => $userProfile,
                'correlation_id' => $correlationId,
            ];
        } catch (Throwable $e) {
            Log::channel('audit')->error('Beauty constructor failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Анализировать фото лица (форма, тон кожи, тип волос)
     */
    private function analyzeFacePhoto(UploadedFile $facePhoto): ?array
    {
        try {
            // Использовать OpenAI Vision API
            $base64Image = base64_encode(file_get_contents($facePhoto->getRealPath()));

            $response = $this->openai->chat()->create([
                'model' => 'gpt-4-vision',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:image/jpeg;base64,{$base64Image}",
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $this->getBeautyAnalysisPrompt(),
                            ],
                        ],
                    ],
                ],
            ]);

            $analysisText = $response->choices[0]->message->content;

            // Парсить JSON из ответа
            preg_match('/\{.*\}/s', $analysisText, $matches);
            if (empty($matches)) {
                return null;
            }

            return json_decode($matches[0], true) ?? null;
        } catch (Throwable $e) {
            Log::warning('Failed to analyze face photo', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Построить полный профиль красоты
     */
    private function buildBeautyProfile(
        UserTasteProfile $tasteProfile,
        ?UserBodyMetrics $bodyMetrics,
        array $faceAnalysis
    ): array {
        $explicit = $tasteProfile->getExplicitPreferences();
        $categoryScores = $tasteProfile->getCategoryScores();

        return [
            // Физические параметры
            'body_metrics' => [
                'height_cm' => $bodyMetrics?->height_cm,
                'weight_kg' => $bodyMetrics?->weight_kg,
                'body_shape' => $bodyMetrics?->body_shape,
                'skin_tone' => $bodyMetrics?->skin_tone,
                'hair_color' => $bodyMetrics?->hair_color,
                'eye_color' => $bodyMetrics?->eye_color,
            ],

            // Анализ лица
            'face_analysis' => $faceAnalysis,

            // Явные предпочтения
            'style_preferences' => $explicit['style_preferences'] ?? [],
            'color_preferences' => $explicit['colors'] ?? [],
            'brands' => $explicit['brands'] ?? [],

            // ML-скоры по категориям красоты
            'category_scores' => [
                'natural_makeup' => $categoryScores['natural_makeup'] ?? 0.5,
                'bold_makeup' => $categoryScores['bold_makeup'] ?? 0.5,
                'skincare' => $categoryScores['skincare'] ?? 0.5,
                'haircare' => $categoryScores['haircare'] ?? 0.5,
            ],
        ];
    }

    /**
     * Рекомендовать причёски
     */
    private function recommendHairstyles(array $userProfile): array
    {
        $faceShape = $userProfile['face_analysis']['face_shape'] ?? 'oval';
        $hairType = $userProfile['face_analysis']['hair_texture'] ?? 'straight';
        $styleScore = $userProfile['category_scores']['bold_makeup'] ?? 0.5;

        $hairstyles = [
            'oval' => [
                ['name' => 'Long waves', 'description' => 'Versatile for all face shapes'],
                ['name' => 'Bob cut', 'description' => 'Classic and elegant'],
                ['name' => 'Curtain bangs', 'description' => 'Soft and flattering'],
            ],
            'round' => [
                ['name' => 'Pixie cut', 'description' => 'Elongates the face'],
                ['name' => 'Asymmetrical bob', 'description' => 'Creates definition'],
            ],
            'square' => [
                ['name' => 'Long waves', 'description' => 'Softens angular features'],
                ['name' => 'Layered cut', 'description' => 'Adds movement and dimension'],
            ],
        ];

        return $hairstyles[$faceShape] ?? $hairstyles['oval'];
    }

    /**
     * Рекомендовать макияж
     */
    private function recommendMakeup(array $userProfile): array
    {
        $skinTone = $userProfile['body_metrics']['skin_tone'] ?? 'warm_beige';
        $eyeColor = $userProfile['body_metrics']['eye_color'] ?? 'brown';
        $makeupScore = $userProfile['category_scores']['bold_makeup'] ?? 0.5;

        $isNatural = $makeupScore < 0.5;

        $makeup = [
            'foundation' => [
                'type' => $isNatural ? 'light_coverage' : 'full_coverage',
                'undertone' => $skinTone,
            ],
            'eyes' => [
                'palette' => $this->getEyePaletteForEye($eyeColor),
                'style' => $isNatural ? 'minimal' : 'dramatic',
            ],
            'lips' => [
                'colors' => $userProfile['color_preferences'] ?? ['nude', 'pink'],
                'finish' => $isNatural ? 'matte' : 'glossy',
            ],
        ];

        return $makeup;
    }

    /**
     * Рекомендовать уход
     */
    private function recommendSkincare(array $userProfile): array
    {
        $skinAnalysis = $userProfile['face_analysis']['skin_type'] ?? 'combination';
        $skincareScore = $userProfile['category_scores']['skincare'] ?? 0.5;

        return [
            'cleanser' => ['type' => $skinAnalysis, 'recommendation' => 'Gentle double cleanse'],
            'toner' => ['recommendation' => 'pH-balancing toner'],
            'essence' => ['recommendation' => 'Hydrating essence (if dry)'],
            'serum' => [
                'types' => $skincareScore > 0.7 ? ['Vitamin C', 'Hyaluronic Acid'] : ['Hyaluronic Acid'],
            ],
            'moisturizer' => ['recommendation' => 'Appropriate for skin type'],
            'sunscreen' => ['spf' => 'SPF 30+'],
        ];
    }

    /**
     * Рекомендовать цвета макияжа и одежды
     */
    private function recommendColors(array $userProfile): array
    {
        $skinTone = $userProfile['body_metrics']['skin_tone'] ?? 'warm_beige';
        $userColors = $userProfile['color_preferences'] ?? [];

        $colorPalettes = [
            'warm_beige' => ['warm_browns', 'golds', 'corals', 'terracottas'],
            'cool_ivory' => ['silvers', 'cool_pinks', 'jewel_tones', 'cool_reds'],
            'deep_olive' => ['forest_greens', 'bronze', 'burgundy', 'gold'],
            'dark_brown' => ['earth_tones', 'rich_colors', 'golds'],
        ];

        return array_merge($userColors, $colorPalettes[$skinTone] ?? []);
    }

    /**
     * Получить подходящую палитру для цвета глаз
     */
    private function getEyePaletteForEye(string $eyeColor): array
    {
        return match ($eyeColor) {
            'blue' => ['warm_oranges', 'warm_browns', 'coppers'],
            'green' => ['purples', 'burgundies', 'bronze'],
            'brown' => ['golds', 'silvers', 'jewel_tones'],
            'hazel' => ['greens', 'golds', 'purples'],
            default => ['neutrals', 'taupes', 'golds'],
        };
    }

    /**
     * Prompt для анализа фото лица
     */
    private function getBeautyAnalysisPrompt(): string
    {
        return <<<'PROMPT'
Analyze this face photo and provide a detailed beauty analysis. Return JSON with:
{
  "face_shape": "oval|round|square|heart|oblong",
  "skin_type": "dry|oily|combination|normal|sensitive",
  "skin_condition": "clear|acne-prone|rosacea|hyperpigmented",
  "hair_texture": "straight|wavy|curly|coily",
  "hair_density": "thin|medium|thick",
  "eye_shape": "almond|round|hooded|deep-set|close-set|wide-set",
  "bone_structure": "delicate|moderate|prominent",
  "recommendations": ["recommendation1", "recommendation2"]
}
PROMPT;
    }
}
