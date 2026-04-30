<?php

declare(strict_types=1);

namespace Modules\Flowers\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\Flowers\Domain\Repositories\FloristRepositoryInterface;
use Modules\Flowers\Domain\Repositories\OrderRepositoryInterface;
use Modules\Flowers\Domain\Entities\Florist;
use Modules\Flowers\Domain\Entities\Order;
use Modules\Flowers\Domain\Enums\OrderStatus;
use Carbon\CarbonImmutable;

final class FloristAssignmentService
{
    use WithAuditLogging;

    public function __construct(
        private FloristRepositoryInterface $floristRepository,
        private OrderRepositoryInterface $orderRepository,
        private readonly AuditService $auditService,
    ) {}

    public function assignBestFlorist(int $orderId): Florist
    {
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            throw new \RuntimeException('Order not found');
        }

        $availableFlorists = $this->floristRepository->getAvailable($order->venueId);
        
        if (empty($availableFlorists)) {
            throw new \RuntimeException('No available florists');
        }

        $bestFlorist = $this->selectBestFlorist($availableFlorists, $order);

        $order = $order->assignFlorist($bestFlorist->id);
        $this->orderRepository->save($order);

        return $bestFlorist;
    }

    public function getFloristWorkload(int $floristId, CarbonImmutable $date): array
    {
        $orders = $this->orderRepository->getByFlorist($floristId, [
            'date_from' => $date->startOfDay()->toDateTimeString(),
            'date_to' => $date->endOfDay()->toDateTimeString(),
        ]);

        $pending = 0;
        $inAssembly = 0;
        $completed = 0;

        foreach ($orders as $order) {
            match ($order->status) {
                OrderStatus::CONFIRMED => $pending++,
                OrderStatus::IN_ASSEMBLY, OrderStatus::ASSEMBLED => $inAssembly++,
                OrderStatus::DELIVERED, OrderStatus::PICKED_UP => $completed++,
                default => null,
            };
        }

        return [
            'florist_id' => $floristId,
            'date' => $date->toDateString(),
            'pending' => $pending,
            'in_assembly' => $inAssembly,
            'completed' => $completed,
            'total' => $pending + $inAssembly + $completed,
        ];
    }

    public function getVenueWorkload(int $venueId, CarbonImmutable $date): array
    {
        $florists = $this->floristRepository->getAvailable($venueId);
        $workloads = [];

        foreach ($florists as $florist) {
            $workloads[] = $this->getFloristWorkload($florist->id, $date);
        }

        return $workloads;
    }

    public function autoAssignPendingOrders(int $venueId): array
    {
        $pendingOrders = $this->orderRepository->getPendingOrders($venueId);
        $assigned = [];

        foreach ($pendingOrders as $order) {
            try {
                $florist = $this->assignBestFlorist($order->id);
                $assigned[] = [
                    'order_id' => $order->id,
                    'order_number' => $order->orderNumber,
                    'florist_id' => $florist->id,
                    'florist_name' => $florist->getFullName(),
                ];
            } catch (\RuntimeException $e) {
                // Skip if no available florists
                continue;
            }
        }

        return $assigned;
    }

    public function getFloristPerformance(int $floristId, int $days = 30): array
    {
        $orders = $this->orderRepository->getByFlorist($floristId, [
            'date_from' => CarbonImmutable::now()->subDays($days)->startOfDay()->toDateTimeString(),
            'date_to' => CarbonImmutable::now()->endOfDay()->toDateTimeString(),
        ]);

        $totalOrders = count($orders);
        $onTime = 0;
        $totalAssemblyTime = 0;
        $completedOrders = 0;

        foreach ($orders as $order) {
            if ($order->status === OrderStatus::DELIVERED || $order->status === OrderStatus::PICKED_UP) {
                $completedOrders++;
                
                if ($order->deliveryDate && $order->deliveredAt) {
                    if ($order->deliveredAt <= $order->deliveryDate) {
                        $onTime++;
                    }
                }

                if ($order->getAssemblyDuration()) {
                    $totalAssemblyTime += $order->getAssemblyDuration();
                }
            }
        }

        $averageAssemblyTime = $completedOrders > 0 ? $totalAssemblyTime / $completedOrders : 0;
        $onTimeRate = $completedOrders > 0 ? ($onTime / $completedOrders) * 100 : 0;

        return [
            'florist_id' => $floristId,
            'period_days' => $days,
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'on_time_deliveries' => $onTime,
            'on_time_rate' => round($onTimeRate, 2),
            'average_assembly_time_minutes' => round($averageAssemblyTime, 2),
        ];
    }

    private function selectBestFlorist(array $florists, Order $order): Florist
    {
        // Score each florist based on multiple factors
        $scores = [];

        foreach ($florists as $florist) {
            $score = 0;

            // Rating factor (30%)
            $score += ($florist->averageRating / 5) * 30;

            // Experience factor (25%)
            $experienceScore = min($florist->ordersCompleted / 100, 1);
            $score += $experienceScore * 25;

            // Availability factor (20%)
            $score += $florist->isAvailable ? 20 : 0;

            // Current workload factor (25%)
            $workload = $this->getFloristWorkload($florist->id, CarbonImmutable::now());
            $workloadScore = max(0, 1 - ($workload['in_assembly'] / 10));
            $score += $workloadScore * 25;

            // Urgency bonus for urgent orders
            if ($order->isUrgent && $florist->isExperienced()) {
                $score += 10;
            }

            $scores[$florist->id] = $score;
        }

        // Sort by score descending
        arsort($scores);

        // Return the florist with the highest score
        $bestFloristId = array_key_first($scores);
        
        foreach ($florists as $florist) {
            if ($florist->id === $bestFloristId) {
                return $florist;
            }
        }

        return $florists[0];
    }

    public function setFloristAvailability(int $floristId, bool $available): Florist
    {
        $florist = $this->floristRepository->findById($floristId);
        
        if (!$florist) {
            throw new \RuntimeException('Florist not found');
        }

        $florist = $florist->setAvailable($available);
        return $this->floristRepository->save($florist);
    }
}
