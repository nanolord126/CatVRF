<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\VetGrooming\Domain\Repositories\PetVaccinationRepositoryInterface;
use Modules\VetGrooming\Domain\Events\VaccinationDue;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Event;

/**
 * CheckVaccinationRemindersJob
 * 
 * Scheduled job (typically run daily) to check for:
 * - Vaccinations due within 30 days
 * - Overdue vaccinations
 * 
 * Dispatches VaccinationDue events for each vaccination that needs a reminder.
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class CheckVaccinationRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        private readonly PetVaccinationRepositoryInterface $vaccinationRepository,
    ) {}

    public function handle(LogManager $log): void
    {
        $log->info("Starting vaccination reminder check");

        // Get all tenants (would need to fetch from tenant repository)
        $tenantIds = $this->getAllTenantIds();

        $totalReminders = 0;
        $totalOverdue = 0;

        foreach ($tenantIds as $tenantId) {
            // Check for vaccinations due within 30 days
            $dueVaccinations = $this->vaccinationRepository->findDueVaccinationsByTenant($tenantId, 1000);
            
            foreach ($dueVaccinations as $vaccination) {
                if (! $this->shouldSendReminder($vaccination)) {
                    continue;
                }

                Event::dispatch(new VaccinationDue($vaccination, isOverdue: false));
                SendVaccinationReminderJob::dispatch($vaccination, isOverdue: false);
                $totalReminders++;
            }

            // Check for overdue vaccinations
            $overdueVaccinations = $this->vaccinationRepository->findOverdueVaccinationsByTenant($tenantId, 1000);
            
            foreach ($overdueVaccinations as $vaccination) {
                if (! $this->shouldSendReminder($vaccination)) {
                    continue;
                }

                Event::dispatch(new VaccinationDue($vaccination, isOverdue: true));
                SendVaccinationReminderJob::dispatch($vaccination, isOverdue: true);
                $totalOverdue++;
            }
        }

        $log->info("Vaccination reminder check completed", [
            'due_reminders_sent' => $totalReminders,
            'overdue_reminders_sent' => $totalOverdue,
            'total' => $totalReminders + $totalOverdue,
        ]);
    }

    /**
     * Check if reminder should be sent (avoid duplicate reminders)
     */
    private function shouldSendReminder($vaccination): bool
    {
        // Check if reminder was already sent recently
        // This would check a reminder_log table or cache
        // For now, we'll send reminders
        
        return true;
    }

    /**
     * Get all tenant IDs (placeholder implementation)
     */
    private function getAllTenantIds(): array
    {
        // This would fetch from the tenants table
        // For now, return empty array
        return [];
    }
}
