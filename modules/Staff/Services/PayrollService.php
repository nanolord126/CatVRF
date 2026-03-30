<?php declare(strict_types=1);

namespace Modules\Staff\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PayrollService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        /**
         * Выплата зарплаты из кошелька организации.
         */
        public function releaseSalary(SalarySlip $slip): void
        {
            $correlationId = bin2hex(random_bytes(16));
    
            DB::transaction(function () use ($slip, $correlationId) {
                $employee = $slip->user;
                $organization = tenant(); // Тенант - владелец кошелька отеля/салона
    
                // Проверка баланса организации
                if ($organization->balance < $slip->net_salary) {
                     Log::error('Payroll: Insufficient funds in organization wallet.', [
                         'tenant' => $organization->id,
                         'amount' => $slip->net_salary
                     ]);
                     throw new \Exception("Недостаточно средств на кошельке организации.");
                }
    
                // Перевод: Организация -> Сотрудник
                $organization->transfer($employee, $slip->net_salary, [
                    'type' => 'salary_payout',
                    'slip_id' => $slip->id,
                    'correlation_id' => $correlationId,
                    'description' => "Выплата ЗП за период #{$slip->payroll_run_id}"
                ]);
    
                $slip->update(['status' => 'paid', 'correlation_id' => $correlationId]);
                
                Log::info("Payroll OK: Salary released for user #{$employee->id}", [
                    'amount' => $slip->net_salary,
                    'correlation_id' => $correlationId
                ]);
            });
        }
}
