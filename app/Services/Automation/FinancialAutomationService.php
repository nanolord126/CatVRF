<?php

namespace App\Services\Automation;

use App\Models\PayrollRun;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class FinancialAutomationService
{
    /**
     * Автоматическая выплата зарплат через Wallet при закрытии периода (PayrollRun).
     */
    public function processAutoPayroll(PayrollRun $run)
    {
        $correlationId = (string) Str::uuid();
        $paidCount = 0;
        $failedCount = 0;

        foreach ($run->salarySlips as $slip) {
            $employee = $slip->user;
            // Канон: Выплата через bavix/laravel-wallet
            // Бизнес-кошелек (Tenant) переводит средства на личный кошелек сотрудника
            try {
                if ($run->tenant->balance >= $slip->net_salary) {
                    $run->tenant->transfer($employee, $slip->net_salary, [
                        'description' => "Auto Payroll: {$run->title} (Slip #{$slip->id})",
                        'correlation_id' => $correlationId,
                        'payroll_run_id' => $run->id,
                    ]);

                    $slip->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'correlation_id' => $correlationId
                    ]);
                    $paidCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        if ($paidCount > 0) {
            $run->update(['status' => 'paid']);
        }

        return [
            'paid' => $paidCount,
            'failed' => $failedCount,
            'correlation_id' => $correlationId
        ];
    }
}
