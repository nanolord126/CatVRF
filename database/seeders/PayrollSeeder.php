<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\EmployeePayrollConfig;
use App\Models\EmployeeDeduction;
use App\Models\PayrollRun;
use App\Models\SalarySlip;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Расчётная ведомость (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            // 1. Create payroll config
            EmployeePayrollConfig::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'base_salary' => rand(1000, 5000),
                    'commission_rate' => 10.00,
                ]
            );

            // 2. Add some deductions
            EmployeeDeduction::create([
                'user_id' => $user->id,
                'amount' => 50,
                'reason' => 'Late arrival fine',
                'date' => now()->subDays(5),
                'status' => 'pending',
                'correlation_id' => Str::uuid(),
            ]);
        }

        // 3. Create one paid payroll run
        $payrollRun = PayrollRun::create([
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
            'status' => 'processed',
            'total_amount' => 0,
            'processed_at' => now()->subMonth()->endOfMonth(),
            'correlation_id' => Str::uuid(),
        ]);

        $totalAmount = 0;
        foreach ($users as $user) {
            $config = $user->payrollConfig;
            $base = $config->base_salary;
            $net = $base - 50; // Simple mock

            SalarySlip::create([
                'payroll_run_id' => $payrollRun->id,
                'user_id' => $user->id,
                'base_salary' => $base,
                'commissions' => 0,
                'bonuses' => 0,
                'deductions' => 50,
                'net_salary' => $net,
                'status' => 'paid',
                'correlation_id' => $payrollRun->correlation_id,
            ]);

            $totalAmount += $net;
        }

        $payrollRun->update(['total_amount' => $totalAmount]);
    }
}
