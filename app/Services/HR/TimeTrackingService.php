<?php declare(strict_types=1);

namespace App\Services\HR;

use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Cache\Repository as Cache;

final readonly class TimeTrackingService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
        private readonly Cache $cache,
    ) {}

    /**
     * Clock in employee
     */
    public function clockIn(
        int $tenantId,
        int $employeeId,
        string $correlationId,
        ?int $shiftId = null,
        array $metadata = []
    ): int {
        $this->fraud->check([
            'operation_type' => 'time_tracking_clock_in',
            'employee_id' => $employeeId,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use (
            $tenantId,
            $employeeId,
            $correlationId,
            $shiftId,
            $metadata
        ) {
            // Check if already clocked in
            $activeEntry = $this->db->table('time_entries')
                ->where('employee_id', $employeeId)
                ->where('clock_out', null)
                ->first();

            if ($activeEntry) {
                throw new \RuntimeException('Employee already clocked in');
            }

            $entryId = $this->db->table('time_entries')->insertGetId([
                'tenant_id' => $tenantId,
                'employee_id' => $employeeId,
                'shift_id' => $shiftId,
                'clock_in' => now(),
                'clock_out' => null,
                'status' => 'active',
                'correlation_id' => $correlationId,
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit->record(
                action: 'employee_clocked_in',
                subjectType: 'time_entry',
                subjectId: $entryId,
                newValues: [
                    'employee_id' => $employeeId,
                    'clock_in' => now()->format('Y-m-d H:i:s'),
                ],
                correlationId: $correlationId,
            );

            $this->logger->channel('audit')->info('Employee clocked in', [
                'entry_id' => $entryId,
                'employee_id' => $employeeId,
                'correlation_id' => $correlationId,
            ]);

            return $entryId;
        });
    }

    /**
     * Clock out employee
     */
    public function clockOut(
        int $entryId,
        string $correlationId,
        ?string $notes = null
    ): bool {
        $this->fraud->check([
            'operation_type' => 'time_tracking_clock_out',
            'entry_id' => $entryId,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($entryId, $correlationId, $notes) {
            $entry = $this->db->table('time_entries')
                ->where('id', $entryId)
                ->first();

            if (!$entry) {
                throw new \RuntimeException('Time entry not found');
            }

            if ($entry->clock_out) {
                throw new \RuntimeException('Already clocked out');
            }

            $clockOut = now();
            $duration = $clockOut->diffInMinutes(\Carbon\Carbon::parse($entry->clock_in));

            $updated = $this->db->table('time_entries')
                ->where('id', $entryId)
                ->update([
                    'clock_out' => $clockOut,
                    'duration_minutes' => $duration,
                    'status' => 'completed',
                    'notes' => $notes,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                $this->audit->record(
                    action: 'employee_clocked_out',
                    subjectType: 'time_entry',
                    subjectId: $entryId,
                    newValues: [
                        'clock_out' => $clockOut->format('Y-m-d H:i:s'),
                        'duration_minutes' => $duration,
                    ],
                    correlationId: $correlationId,
                );
            }

            return $updated > 0;
        });
    }

    /**
     * Get employee time entries for date range
     */
    public function getTimeEntries(
        int $employeeId,
        \DateTime $startDate,
        \DateTime $endDate,
        string $correlationId
    ): array {
        $cacheKey = "time_entries:{$employeeId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return $this->cache->remember($cacheKey, 3600, function () use ($employeeId, $startDate, $endDate) {
            return $this->db->table('time_entries')
                ->where('employee_id', $employeeId)
                ->whereBetween('clock_in', [$startDate, $endDate])
                ->orderBy('clock_in', 'desc')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get total hours worked in period
     */
    public function getTotalHours(
        int $employeeId,
        \DateTime $startDate,
        \DateTime $endDate,
        string $correlationId
    ): array {
        $entries = $this->getTimeEntries($employeeId, $startDate, $endDate, $correlationId);

        $totalMinutes = collect($entries)->sum('duration_minutes');
        $totalHours = $totalMinutes / 60;
        $overtimeMinutes = collect($entries)->filter(fn($e) => $e->duration_minutes > 480)->sum('duration_minutes') - (count($entries) * 480);
        $overtimeHours = max(0, $overtimeMinutes / 60);

        return [
            'total_hours' => round($totalHours, 2),
            'total_minutes' => $totalMinutes,
            'overtime_hours' => round($overtimeHours, 2),
            'overtime_minutes' => $overtimeMinutes,
            'entry_count' => count($entries),
        ];
    }

    /**
     * Auto-clock out employees who forgot
     */
    public function autoClockOutInactiveEmployees(
        int $tenantId,
        int $maxHours = 12,
        string $correlationId
    ): int {
        $cutoffTime = now()->subHours($maxHours);

        $inactiveEntries = $this->db->table('time_entries')
            ->where('tenant_id', $tenantId)
            ->where('clock_out', null)
            ->where('clock_in', '<', $cutoffTime)
            ->get();

        $clockedOut = 0;

        foreach ($inactiveEntries as $entry) {
            try {
                $this->clockOut(
                    $entry->id,
                    $correlationId,
                    'Auto clock out - exceeded maximum hours'
                );
                $clockedOut++;
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error('Auto clock out failed', [
                    'entry_id' => $entry->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        $this->logger->channel('audit')->info('Auto clock out completed', [
            'tenant_id' => $tenantId,
            'clocked_out_count' => $clockedOut,
            'correlation_id' => $correlationId,
        ]);

        return $clockedOut;
    }
}
