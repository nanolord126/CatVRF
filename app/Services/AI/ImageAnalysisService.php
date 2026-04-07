<?php declare(strict_types=1);

namespace App\Services\AI;


use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

use OpenAI\Client;
use Illuminate\Log\LogManager;

final readonly class ImageAnalysisService
{
    public function __construct(
        private readonly Request $request,
            private Client $openai,
        private readonly LogManager $logger,
    ) {}

        /**
         * Анализировать загруженное фото через OpenAI Vision API
         *
         * @param UploadedFile $photo Загруженное фото
         * @param string $prompt Детальный промпт для анализа
         * @param array $context Контекст (например, вертикаль конструктора)
         * @return array Результаты анализа {description, features, elements, colors, styles, recommendations, confidence}
         */
        public function analyze(UploadedFile $photo, string $prompt, array $context = []): array
        {
            try {
                // Читать содержимое файла
                $photoContent = \file_get_contents($photo->getRealPath());
                $base64Photo = \base64_encode($photoContent);
                $mimeType = $photo->getMimeType();

                // Подготовить детальный промпт
                $detailedPrompt = $this->buildPrompt($prompt, $context);

                // Отправить в OpenAI Vision API
                $response = $this->openai->messages()->create([
                    'model' => 'gpt-4-vision',
                    'max_tokens' => 2000,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'image',
                                    'source' => [
                                        'type' => 'base64',
                                        'media_type' => $mimeType,
                                        'data' => $base64Photo,
                                    ],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $detailedPrompt,
                                ],
                            ],
                        ],
                    ],
                ]);

                // Парсить ответ
                $analysisText = $response->content[0]->text;
                $analysis = $this->parseAnalysis($analysisText, $context);

                $this->logger->channel('audit')->info('Image analysis completed', [
                    'file_name' => $photo->getClientOriginalName(),
                    'file_size' => $photo->getSize(),
                    'context' => $context,
                    'confidence' => $analysis['confidence'],
                ]);

                return $analysis;
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Image analysis failed', [
                    'file_name' => $photo->getClientOriginalName() ?? 'unknown',
                    'error' => $e->getMessage(),
                    'context' => $context,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                throw new \RuntimeException("Ошибка анализа фото: {$e->getMessage()}");
            }
        }

        /**
         * Построить детальный промпт для анализа
         */
        private function buildPrompt(string $userPrompt, array $context): string
        {
            $verticalHint = match ($context['vertical'] ?? null) {
                'beauty_look' => 'Анализируй внешность: тип лица, цвет кожи, волос, рекомендации макияжа, причёски, украшений.',
                'outfit' => 'Анализируй текущий наряд и стиль: цвета, фасоны, материалы, аксессуары, возможные комбинации.',
                'cake' => 'Анализируй пространство, оцени объём, стиль украшения, цветовую палитру.',
                'menu' => 'Анализируй окружение: время дня, количество людей, тип мероприятия, атмосфера.',
                default => '',
            };

            return <<<EOT
    $userPrompt

    $verticalHint

    Пожалуйста, верни результат в формате JSON с полями:
    {
      "description": "Краткое описание проанализированного",
      "features": ["список заметных особенностей"],
      "colors": ["основные цвета из 5-7"],
      "styles": ["определённые стили или направления"],
      "elements": ["ключевые элементы"],
      "recommendations": ["3-5 рекомендаций для улучшения"],
      "confidence": 0.85
    }

    Верни ТОЛЬКО корректный JSON без markdown-блоков.
    EOT;
        }

        /**
         * Парсить ответ от OpenAI
         */
        private function parseAnalysis(string $responseText, array $context): array
        {
            try {
                // Очистить ответ от markdown блоков
                $json = \preg_replace('/^```json\n?|\n?```$/m', '', $responseText);
                $json = \trim($json);

                $parsed = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

                return [
                    'description' => $parsed['description'] ?? '',
                    'features' => $parsed['features'] ?? [],
                    'colors' => $parsed['colors'] ?? [],
                    'styles' => $parsed['styles'] ?? [],
                    'elements' => $parsed['elements'] ?? [],
                    'recommendations' => $parsed['recommendations'] ?? [],
                    'confidence' => (float)($parsed['confidence'] ?? 0.5),
                    'vertical' => $context['vertical'] ?? 'unknown',
                    'analysis_timestamp' => now()->toIso8601String(),
                ];
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->warning('Failed to parse OpenAI response', [
                    'error' => $e->getMessage(),
                    'response_sample' => \substr($responseText, 0, 200),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

                // Fallback: базовый анализ
                return [
                    'description' => $responseText,
                    'features' => [],
                    'colors' => [],
                    'styles' => [],
                    'elements' => [],
                    'recommendations' => [],
                    'confidence' => 0.3,
                    'vertical' => $context['vertical'] ?? 'unknown',
                    'analysis_timestamp' => now()->toIso8601String(),
                ];
            }
        }

        /**
         * Сохранить фото в storage
         */
        public function storePhoto(UploadedFile $photo, string $type): string
        {
            $path = "ai-constructions/{$type}/" . \uniqid() . '.' . $photo->getClientOriginalExtension();
            $photo->storeAs(\dirname($path), \basename($path), 'public');

            return $path;
        }
}
