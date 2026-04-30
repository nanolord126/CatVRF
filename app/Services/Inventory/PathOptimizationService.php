<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Path Optimization Service
 * 
 * Сервис для оптимизации маршрутов комплектации
 * Использует алгоритмы для минимизации времени перемещения
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class PathOptimizationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Оптимизация маршрута комплектации
     */
    public function optimizePickPath(array $pickTasks, int $warehouseId): array
    {
        if (empty($pickTasks)) {
            return [];
        }

        // Получение координат всех локаций
        $locations = $this->getLocationsCoordinates($pickTasks, $warehouseId);

        // Применение алгоритма ближайшего соседа (Nearest Neighbor)
        $optimizedPath = $this->applyNearestNeighbor($pickTasks, $locations);

        // Расчет метрик оптимизации
        $metrics = $this->calculatePathMetrics($optimizedPath, $locations);

        $this->logger->info('Pick path optimized', [
            'warehouse_id' => $warehouseId,
            'task_count' => count($pickTasks),
            'metrics' => $metrics,
        ]);

        return [
            'optimized_tasks' => $optimizedPath,
            'metrics' => $metrics,
        ];
    }

    /**
     * Получение координат локаций
     */
    private function getLocationsCoordinates(array $pickTasks, int $warehouseId): array
    {
        $locationIds = array_unique(array_column($pickTasks, 'location_id'));

        $locations = $this->db->table('inventory_locations')
            ->whereIn('id', $locationIds)
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->keyBy('id');

        $coordinates = [];
        foreach ($locationIds as $locationId) {
            $location = $locations->get($locationId);
            $coordinates[$locationId] = [
                'x' => $location ? $location->x_coordinate ?? 0 : 0,
                'y' => $location ? $location->y_coordinate ?? 0 : 0,
                'z' => $location ? $location->z_coordinate ?? 0 : 0,
            ];
        }

        return $coordinates;
    }

    /**
     * Алгоритм ближайшего соседа
     */
    private function applyNearestNeighbor(array $tasks, array $coordinates): array
    {
        if (empty($tasks)) {
            return [];
        }

        $unvisited = $tasks;
        $path = [];
        $currentLocation = ['x' => 0, 'y' => 0, 'z' => 0]; // Точка начала (вход)

        while (!empty($unvisited)) {
            $nearestIndex = $this->findNearestLocation($currentLocation, $unvisited, $coordinates);
            $nearestTask = $unvisited[$nearestIndex];

            $path[] = $nearestTask;
            $currentLocation = $coordinates[$nearestTask['location_id']];

            array_splice($unvisited, $nearestIndex, 1);
        }

        return $path;
    }

    /**
     * Поиск ближайшей локации
     */
    private function findNearestLocation(array $current, array $tasks, array $coordinates): int
    {
        $nearestIndex = 0;
        $nearestDistance = PHP_FLOAT_MAX;

        foreach ($tasks as $index => $task) {
            $taskLocation = $coordinates[$task['location_id']];
            $distance = $this->calculateEuclideanDistance($current, $taskLocation);

            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestIndex = $index;
            }
        }

        return $nearestIndex;
    }

    /**
     * Расчет евклидова расстояния
     */
    private function calculateEuclideanDistance(array $point1, array $point2): float
    {
        $dx = $point1['x'] - $point2['x'];
        $dy = $point1['y'] - $point2['y'];
        $dz = $point1['z'] - $point2['z'];

        return sqrt($dx * $dx + $dy * $dy + $dz * $dz);
    }

    /**
     * Расчет метрик маршрута
     */
    private function calculatePathMetrics(array $path, array $coordinates): array
    {
        if (empty($path)) {
            return [
                'total_distance' => 0,
                'estimated_time_seconds' => 0,
                'location_count' => 0,
            ];
        }

        $totalDistance = 0;
        $currentLocation = ['x' => 0, 'y' => 0, 'z' => 0];

        foreach ($path as $task) {
            $taskLocation = $coordinates[$task['location_id']];
            $totalDistance += $this->calculateEuclideanDistance($currentLocation, $taskLocation);
            $currentLocation = $taskLocation;
        }

        // Возврат к точке начала
        $totalDistance += $this->calculateEuclideanDistance($currentLocation, ['x' => 0, 'y' => 0, 'z' => 0]);

        // Оценка времени: 1 метр = 2 секунды + 10 секунд на локацию
        $estimatedTime = ($totalDistance * 2) + (count($path) * 10);

        return [
            'total_distance' => round($totalDistance, 2),
            'estimated_time_seconds' => (int) $estimatedTime,
            'location_count' => count($path),
        ];
    }

    /**
     * Оптимизация по зонам (zone-based)
     */
    public function optimizeByZone(array $pickTasks, int $warehouseId): array
    {
        // Группировка задач по зонам
        $groupedByZone = [];
        foreach ($pickTasks as $task) {
            $location = $this->db->table('inventory_locations')
                ->where('id', $task['location_id'])
                ->first();

            $zone = $location ? $location->zone : 'unassigned';

            if (!isset($groupedByZone[$zone])) {
                $groupedByZone[$zone] = [];
            }
            $groupedByZone[$zone][] = $task;
        }

        // Оптимизация внутри каждой зоны
        $optimizedTasks = [];
        foreach ($groupedByZone as $zone => $zoneTasks) {
            $zoneOptimized = $this->optimizePickPath($zoneTasks, $warehouseId);
            $optimizedTasks = array_merge($optimizedTasks, $zoneOptimized['optimized_tasks']);
        }

        return [
            'optimized_tasks' => $optimizedTasks,
            'zones_processed' => count($groupedByZone),
        ];
    }

    /**
     * Сравнение эффективности оптимизации
     */
    public function compareOptimization(array $originalTasks, array $optimizedTasks, int $warehouseId): array
    {
        $originalCoordinates = $this->getLocationsCoordinates($originalTasks, $warehouseId);
        $optimizedCoordinates = $this->getLocationsCoordinates($optimizedTasks, $warehouseId);

        $originalMetrics = $this->calculatePathMetrics($originalTasks, $originalCoordinates);
        $optimizedMetrics = $this->calculatePathMetrics($optimizedTasks, $optimizedCoordinates);

        $distanceImprovement = $originalMetrics['total_distance'] - $optimizedMetrics['total_distance'];
        $timeImprovement = $originalMetrics['estimated_time_seconds'] - $optimizedMetrics['estimated_time_seconds'];
        $distanceImprovementPercent = ($distanceImprovement / $originalMetrics['total_distance']) * 100;
        $timeImprovementPercent = ($timeImprovement / $originalMetrics['estimated_time_seconds']) * 100;

        return [
            'original_distance' => $originalMetrics['total_distance'],
            'optimized_distance' => $optimizedMetrics['total_distance'],
            'distance_improvement' => round($distanceImprovement, 2),
            'distance_improvement_percent' => round($distanceImprovementPercent, 2),
            'original_time' => $originalMetrics['estimated_time_seconds'],
            'optimized_time' => $optimizedMetrics['estimated_time_seconds'],
            'time_improvement' => $timeImprovement,
            'time_improvement_percent' => round($timeImprovementPercent, 2),
        ];
    }
}
