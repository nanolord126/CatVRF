<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

final class MemoryProfilingService
{
    private static array $snapshots = [];

    private static array $profileData = [];

    public function __construct(private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly LogManager $logger,) {}

    /**
     * Создаёт снимок памяти
     */
    public static function takeSnapshot(string $label): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $realMemory = memory_get_usage();

        $snapshot = [
            'label' => $label,
            'timestamp' => CarbonImmutable::now()->toDateTimeString(),
            'memory_usage' => $memoryUsage,
            'memory_peak' => $memoryPeak,
            'real_memory' => $realMemory,
            'memory_usage_human' => self::bytesToHuman($memoryUsage),
            'memory_peak_human' => self::bytesToHuman($memoryPeak),
        ];

        self::$snapshots[$label] = $snapshot;

        $this->logger->channel('performance')->debug('Memory snapshot taken', array_merge($snapshot, ['correlation_id' => $this->request?->header('X-Correlation-ID') ?? '']));

        return $snapshot;
    }

    /**
     * Сравнивает два снимка памяти
     *
     * @return array|null
     */
    public static function compareSnapshots(string $label1, string $label2): array
    {
        if (! isset(self::$snapshots[$label1]) || ! isset(self::$snapshots[$label2])) {
            throw new \InvalidArgumentException(
                "One or both memory snapshots not found: '{$label1}', '{$label2}'"
            );
        }

        $snap1 = self::$snapshots[$label1];
        $snap2 = self::$snapshots[$label2];

        $memoryDiff = $snap2['memory_usage'] - $snap1['memory_usage'];
        $memoryDiffPercent = ($memoryDiff / $snap1['memory_usage']) * 100;

        return [
            'snapshot_1' => $label1,
            'snapshot_2' => $label2,
            'memory_diff_bytes' => $memoryDiff,
            'memory_diff_human' => self::bytesToHuman($memoryDiff),
            'memory_diff_percent' => round($memoryDiffPercent, 2),
            'snapshot_1_memory' => $snap1['memory_usage_human'],
            'snapshot_2_memory' => $snap2['memory_usage_human'],
            'leak_detected' => $memoryDiff > 0,
        ];
    }

    /**
     * Профилирует выполнение кода
     */
    public static function profile(callable $callback, string $label = 'profile'): mixed
    {
        self::takeSnapshot($label.'_start');
        $startTime = microtime(true);

        $result = $callback();

        $endTime = microtime(true);
        self::takeSnapshot($label.'_end');

        $comparison = self::compareSnapshots($label.'_start', $label.'_end');

        self::$profileData[$label] = [
            'duration_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_change' => $comparison['memory_diff_human'] ?? 'N/A',
            'memory_change_percent' => $comparison['memory_diff_percent'] ?? 0,
        ];

        $this->logger->channel('performance')->$this->logger->info('Code profiling completed', [
            'label' => $label,
            'duration_ms' => self::$profileData[$label]['duration_ms'],
            'memory_change' => self::$profileData[$label]['memory_change'],
            'correlation_id' => $this->request?->header('X-Correlation-ID') ?? '',
        ]);

        return $result;
    }

    /**
     * Получает информацию об объектах в памяти
     */
    public static function getObjectStats(): array
    {
        $objects = [];

        // Получаем все объекты из GLOBALS
        $stats = [
            'total_memory_bytes' => memory_get_usage(true),
            'total_memory_human' => self::bytesToHuman(memory_get_usage(true)),
            'peak_memory_bytes' => memory_get_peak_usage(true),
            'peak_memory_human' => self::bytesToHuman(memory_get_peak_usage(true)),
            'memory_limit' => ini_get('memory_limit'),
        ];

        return $stats;
    }

    /**
     * Получает размер переменной
     */
    public static function getVariableSize(mixed $var): int
    {
        $size = strlen(serialize($var));

        return $size;
    }

    /**
     * Получает размер переменной в читаемом формате
     */
    public static function getVariableSizeHuman(mixed $var): string
    {
        return self::bytesToHuman(self::getVariableSize($var));
    }

    /**
     * Получает размер массива
     */
    public static function getArraySize(array $array): int
    {
        $size = 0;

        foreach ($array as $key => $value) {
            $size += strlen($key);

            if (is_array($value)) {
                $size += self::getArraySize($value);
            } else {
                $size += strlen(serialize($value));
            }
        }

        return $size;
    }

    /**
     * Отчёт об использовании памяти
     */
    public static function generateReport(): string
    {
        $stats = self::getObjectStats();

        $report = "\n╔════════════════════════════════════════════════════════════╗\n";
        $report .= "║            MEMORY USAGE REPORT                             ║\n";
        $report .= '║            '.CarbonImmutable::now()->toDateTimeString()."                    ║\n";
        $report .= "╚════════════════════════════════════════════════════════════╝\n\n";

        $report .= sprintf("  %-40s: %s\n", 'Current Memory Usage', $stats['total_memory_human']);
        $report .= sprintf("  %-40s: %s\n", 'Peak Memory Usage', $stats['peak_memory_human']);
        $report .= sprintf("  %-40s: %s\n", 'Memory Limit', $stats['memory_limit']);

        if (! empty(self::$profileData)) {
            $report .= "\n  PROFILE DATA:\n";

            foreach (self::$profileData as $label => $data) {
                $report .= sprintf(
                    "    %-36s: %s (Δ%s, %s%%)\n",
                    $label,
                    $data['duration_ms'].'ms',
                    $data['memory_change'],
                    $data['memory_change_percent']
                );
            }
        }

        $report .= "\n";

        return $report;
    }

    /**
     * Очищает кэш snapshots
     */
    public static function clearSnapshots(): void
    {
        self::$snapshots = [];
        self::$profileData = [];
    }

    /**
     * Преобразует байты в читаемый формат
     */
    private static function bytesToHuman(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
