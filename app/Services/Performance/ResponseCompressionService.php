<?php declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ResponseCompressionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private const MIN_COMPRESSION_SIZE = 1024; // Компрессуем если > 1KB
        private const COMPRESSION_LEVEL = 6; // 1-9 (6 = хороший баланс)

        /**
         * Компрессирует JSON-ответ и добавляет заголовки
         *
         * @param JsonResponse $response
         * @return JsonResponse
         */
        public static function compress(JsonResponse $response): JsonResponse
        {
            try {
                $content = $response->getContent();
                $contentSize = strlen($content);

                // Проверяем, имеет ли смысл компрессовать
                if ($contentSize < self::MIN_COMPRESSION_SIZE) {
                    return $response;
                }

                // Проверяем поддержку gzip в браузере
                $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
                if (strpos($acceptEncoding, 'gzip') === false) {
                    return $response;
                }

                // Компрессуем
                $compressed = gzencode($content, self::COMPRESSION_LEVEL);
                $compressedSize = strlen($compressed);

                // Экономия трафика?
                $savings = (($contentSize - $compressedSize) / $contentSize) * 100;

                if ($savings < 5) {
                    // Недостаточная экономия
                    return $response;
                }

                // Обновляем ответ
                $response->setContent($compressed);
                $response->header('Content-Encoding', 'gzip');
                $response->header('Content-Length', $compressedSize);
                $response->header('X-Original-Size', $contentSize);
                $response->header('X-Compression-Ratio', round($savings, 1) . '%');

                Log::channel('performance')->debug('Response compressed', [
                    'original_size' => $contentSize,
                    'compressed_size' => $compressedSize,
                    'savings_percent' => round($savings, 1)
                ]);

                return $response;

            } catch (\Throwable $e) {
                Log::channel('performance')->warning('Response compression failed', [
                    'error' => $e->getMessage()
                ]);
                return $response;
            }
        }

        /**
         * Минифицирует JSON для удаления пробелов
         *
         * @param string $json
         * @return string
         */
        public static function minifyJson(string $json): string
        {
            $json = preg_replace('/\s+/', ' ', $json); // Удаляем лишние пробелы
            $json = preg_replace('/\s*([{}\[\]:,])\s*/', '$1', $json); // Убираем пробелы вокруг скобок

            return $json;
        }

        /**
         * Добавляет заголовки кэширования для оптимизации
         *
         * @param JsonResponse $response
         * @param int $cacheTtl
         * @return JsonResponse
         */
        public static function withCacheHeaders(JsonResponse $response, int $cacheTtl = 3600): JsonResponse
        {
            $response->header('Cache-Control', "public, max-age={$cacheTtl}");
            $response->header('ETag', '"' . md5($response->getContent()) . '"');
            $response->header('Last-Modified', now()->toRfc7231String());

            return $response;
        }

        /**
         * Удаляет чувствительные поля из ответа для публичных API
         *
         * @param array $data
         * @param array $fieldsToRemove
         * @return array
         */
        public static function stripSensitiveData(array $data, array $fieldsToRemove = []): array
        {
            $defaultSensitiveFields = ['password', 'token', 'secret', 'api_key', 'private_key'];
            $fields = array_merge($defaultSensitiveFields, $fieldsToRemove);

            return self::recursiveRemove($data, $fields);
        }

        /**
         * Рекурсивно удаляет поля
         *
         * @param array $data
         * @param array $fieldsToRemove
         * @return array
         */
        private static function recursiveRemove(array $data, array $fieldsToRemove): array
        {
            foreach ($data as $key => &$value) {
                if (in_array($key, $fieldsToRemove)) {
                    unset($data[$key]);
                    continue;
                }

                if (is_array($value)) {
                    $value = self::recursiveRemove($value, $fieldsToRemove);
                }
            }

            return $data;
        }

        /**
         * Измеряет размер передачи данных
         *
         * @param string $content
         * @return array {original_size, gzip_size, savings_percent}
         */
        public static function getCompressionMetrics(string $content): array
        {
            $originalSize = strlen($content);
            $gzipSize = strlen(gzencode($content, self::COMPRESSION_LEVEL));
            $savings = (($originalSize - $gzipSize) / $originalSize) * 100;

            return [
                'original_size' => $originalSize,
                'gzip_size' => $gzipSize,
                'savings_percent' => round($savings, 1),
                'original_size_human' => self::bytesToHuman($originalSize),
                'gzip_size_human' => self::bytesToHuman($gzipSize),
            ];
        }

        /**
         * Преобразует байты в человеческий формат
         *
         * @param int $bytes
         * @return string
         */
        private static function bytesToHuman(int $bytes): string
        {
            $units = ['B', 'KB', 'MB', 'GB'];
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= (1 << (10 * $pow));

            return round($bytes, 2) . ' ' . $units[$pow];
        }
}
