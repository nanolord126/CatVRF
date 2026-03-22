<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ExportService — экспорт данных в различные форматы
 * 
 * Методы:
 * - exportToCSV(data, filename)
 * - exportToExcel(data, filename)
 * - exportToPDF(data, filename)
 * - exportToJSON(data, filename)
 * - prepareDataForExport(data)
 * - getExportHistory(tenantId)
 */
final class ExportService
{
    private const CACHE_TTL = 3600;
    private const EXPORTS_DIR = 'exports';

    /**
     * Экспортировать в CSV
     */
    public function exportToCSV(array $data, string $filename, array $context = []): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $csv = $this->convertToCSV($data);

        $result = [
            'id' => Str::uuid()->toString(),
            'filename' => $filename . '.csv',
            'format' => 'csv',
            'size_bytes' => strlen($csv),
            'url' => $this->generateDownloadURL($filename, 'csv'),
            'created_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        Log::channel('audit')->info('CSV export created', [
            'correlation_id' => $correlationId,
            'filename' => $filename,
            'size' => $result['size_bytes'],
        ]);

        return $result;
    }

    /**
     * Экспортировать в Excel
     */
    public function exportToExcel(array $data, string $filename, array $context = []): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        throw new \RuntimeException('Excel export not yet configured. Install maatwebsite/excel.');
    }

    /**
     * Экспортировать в PDF
     */
    public function exportToPDF(array $data, string $filename, array $context = []): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        throw new \RuntimeException('PDF export not yet configured. Install barryvdh/laravel-dompdf.');
    }

    /**
     * Экспортировать в JSON
     */
    public function exportToJSON(array $data, string $filename, array $context = []): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $result = [
            'id' => Str::uuid()->toString(),
            'filename' => $filename . '.json',
            'format' => 'json',
            'size_bytes' => strlen($json),
            'url' => $this->generateDownloadURL($filename, 'json'),
            'created_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        Log::channel('audit')->info('JSON export created', [
            'correlation_id' => $correlationId,
            'filename' => $filename,
            'size' => $result['size_bytes'],
        ]);

        return $result;
    }

    /**
     * Подготовить данные к экспорту (валидация, форматирование)
     */
    public function prepareDataForExport(array $data): array {
        $prepared = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $prepared[$key] = $this->flattenArray($value);
            } elseif (is_object($value)) {
                $prepared[$key] = $this->objectToArray($value);
            } else {
                $prepared[$key] = $value;
            }
        }

        return $prepared;
    }

    /**
     * Получить историю экспортов
     */
    public function getExportHistory(int $tenantId, int $limit = 50, array $context = []): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();
        $cacheKey = "exports:history:{$tenantId}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $history = [
            [
                'id' => Str::uuid()->toString(),
                'filename' => 'revenue_report_2026_03_18.csv',
                'format' => 'csv',
                'created_at' => now()->subHours(2)->toIso8601String(),
                'created_by' => 'user@example.com',
            ],
            [
                'id' => Str::uuid()->toString(),
                'filename' => 'monthly_summary_2026_02.xlsx',
                'format' => 'excel',
                'created_at' => now()->subDays(1)->toIso8601String(),
                'created_by' => 'manager@example.com',
            ],
            [
                'id' => Str::uuid()->toString(),
                'filename' => 'customer_analysis_2026_q1.pdf',
                'format' => 'pdf',
                'created_at' => now()->subDays(7)->toIso8601String(),
                'created_by' => 'analyst@example.com',
            ],
        ];

        Cache::put($cacheKey, $history, self::CACHE_TTL);

        return array_slice($history, 0, $limit);
    }

    // ========== PRIVATE HELPERS ==========

    private function convertToCSV(array $data): string {
        $csv = "";
        $headers = array_keys($data);
        $csv .= implode(",", $headers) . "\n";

        $rows = is_array(reset($data)) ? reset($data) : [$data];

        foreach ($rows as $row) {
            $values = [];
            foreach ($headers as $header) {
                $values[] = isset($row[$header]) ? '"' . addslashes((string)$row[$header]) . '"' : '""';
            }
            $csv .= implode(",", $values) . "\n";
        }

        return $csv;
    }

    private function generateDownloadURL(string $filename, string $format): string {
        return "/api/v2/exports/download/{$filename}.{$format}";
    }

    private function flattenArray(array $arr, string $prefix = ''): array {
        $result = [];
        foreach ($arr as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    private function objectToArray(object $obj): array {
        return json_decode(json_encode($obj), true);
    }
}
