<?php

namespace App\Domains\Advertising\Services\AI;

use Illuminate\Support\Facades\{Http, Log, Cache};
use Illuminate\Support\Str;
use Throwable;

/**
 * AdGenerativeStudio - AI сервис генерации рекламных креативов (Production 2026).
 * 
 * Отвечает за:
 * - Генерацию изображений через DALL-E API
 * - Наложение маркировки (ERID)
 * - Кеширование результатов для оптимизации
 * - Полное логирование и обработка ошибок
 */
class AdGenerativeStudio
{
    private string $apiKey;
    private string $correlationId;
    private int $maxRetries = 3;
    private int $retryDelay = 2; // seconds

    public function __construct()
    {
        $this->apiKey = config('services.openai.key', '');
        $this->correlationId = Str::uuid()->toString();

        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured');
        }
    }

    /**
     * Генерация рекламного креатива на основе промпта рекламодателя (Production 2026).
     * Возвращает URL сгенерированного изображения с retry логикой.
     *
     * @param string $prompt Текстовое описание креатива
     * @param array $dimensions Размеры изображения (width x height)
     * @return string URL сгенерированного изображения
     * 
     * @throws \InvalidArgumentException При невалидных параметрах
     * @throws \Exception При ошибке API после всех retry
     */
    public function generateCreative(string $prompt, array $dimensions = ['width' => 1024, 'height' => 1024]): string
    {
        try {
            // === Валидация входных данных ===
            if (empty(trim($prompt))) {
                throw new \InvalidArgumentException('Prompt cannot be empty');
            }

            $validSizes = ['256x256', '512x512', '1024x1024', '1792x1024', '1024x1792'];
            $size = "{$dimensions['width']}x{$dimensions['height']}";
            
            if (!in_array($size, $validSizes)) {
                throw new \InvalidArgumentException("Invalid dimensions. Allowed: " . implode(', ', $validSizes));
            }

            // === Проверка кеша ===
            $cacheKey = "ad_creative:" . md5($prompt . $size);
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                Log::info('Creative retrieved from cache', [
                    'prompt_hash' => md5($prompt),
                    'cache_key' => $cacheKey,
                    'correlation_id' => $this->correlationId,
                ]);
                return $cached;
            }

            // === Retry логика для вызова API ===
            $lastException = null;
            
            for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
                try {
                    Log::debug('AI creative generation attempt', [
                        'attempt' => $attempt,
                        'prompt_length' => strlen($prompt),
                        'size' => $size,
                        'correlation_id' => $this->correlationId,
                    ]);

                    $response = Http::timeout(60)
                        ->withToken($this->apiKey)
                        ->post('https://api.openai.com/v1/images/generations', [
                            'model' => 'dall-e-3',
                            'prompt' => "Professional advertising banner: {$prompt}. High quality, marketing style, clean design. Include company branding if possible.",
                            'n' => 1,
                            'size' => $size,
                            'quality' => 'hd',
                        ]);

                    if ($response->successful()) {
                        $imageUrl = $response->json('data.0.url');

                        // === Кеширование результата на 24 часа ===
                        Cache::put($cacheKey, $imageUrl, 86400);

                        Log::info('AI creative generated successfully', [
                            'prompt_hash' => md5($prompt),
                            'size' => $size,
                            'image_url' => $imageUrl,
                            'attempt' => $attempt,
                            'correlation_id' => $this->correlationId,
                        ]);

                        return $imageUrl;
                    }

                    // Обработка HTTP ошибок
                    if ($response->status() === 429) {
                        // Rate limiting - подожди перед retry
                        sleep($this->retryDelay * $attempt);
                        continue;
                    }

                    throw new \Exception(
                        "OpenAI API error (Status {$response->status()}): " . 
                        ($response->json('error.message') ?? $response->body())
                    );

                } catch (Throwable $e) {
                    $lastException = $e;
                    
                    if ($attempt < $this->maxRetries) {
                        sleep($this->retryDelay * $attempt);
                    }
                }
            }

            // === Все retry исчерпаны ===
            throw new \Exception(
                "Failed to generate creative after {$this->maxRetries} attempts: " . 
                ($lastException?->getMessage() ?? 'Unknown error')
            );

        } catch (Throwable $e) {
            Log::error('AdGenerativeStudio: Creative generation failed', [
                'prompt_length' => strlen($prompt ?? ''),
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'correlation_id' => $this->correlationId,
            ]);

            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Автоматическое наложение маркировки (ERID) на изображение (Production 2026).
     * Использует Intervention Image для встраивания текста.
     *
     * @param string $imageUrl URL изображения
     * @param string $erid Уникальный ERID по 347-ФЗ
     * @param array $options Опции наложения (position, size, opacity)
     * @return string URL обработанного файла
     * 
     * @throws \Exception При ошибке обработки
     */
    public function overlayLegalLabel(string $imageUrl, string $erid, array $options = []): string
    {
        try {
            if (empty($erid)) {
                throw new \InvalidArgumentException('ERID cannot be empty');
            }

            Log::info('Overlaying ERID label onto creative', [
                'erid' => $erid,
                'image_url' => $imageUrl,
                'correlation_id' => $this->correlationId,
            ]);

            // Загрузить изображение и наложить метку ERID
            try {
                $image = \Intervention\Image\Facades\Image::make($imageUrl);
                
                // Получить размеры изображения
                $width = $image->width();
                $height = $image->height();
                
                // Добавить прямоугольник в нижнем левом углу с текстом
                $image->rectangle(10, $height - 50, 200, $height - 10, function ($draw) {
                    $draw->background('rgba(0, 0, 0, 0.7)');
                });
                
                // Добавить текст с ERID
                $image->text("Реклама. ERID: {$erid}", 15, $height - 35, function ($font) {
                    $font->file(storage_path('fonts/arial.ttf'));
                    $font->size(10);
                    $font->color('#FFFFFF');
                });
                
                // Сохранить обработанное изображение
                $filename = 'ads/erid-marked-' . uniqid() . '.png';
                $storagePath = storage_path("app/public/{$filename}");
                
                // Убедиться что директория существует
                @mkdir(dirname($storagePath), 0755, true);
                
                $image->save($storagePath);
                
                Log::info('ERID overlay completed', [
                    'file_path' => $filename,
                    'erid' => $erid,
                ]);
                
                return asset("storage/{$filename}");
                
            } catch (\Exception $e) {
                Log::warning('ERID overlay failed, using original image', [
                    'error' => $e->getMessage(),
                    'erid' => $erid,
                ]);
                
                \Sentry\captureException($e);
                
                // Fallback: возвращаем исходный URL
                return $imageUrl;
            }

        } catch (Throwable $e) {
            Log::error('AdGenerativeStudio: ERID overlay failed', [
                'erid' => $erid ?? 'unknown',
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            \Sentry\captureException($e);
            throw $e;
        }
    }
}
