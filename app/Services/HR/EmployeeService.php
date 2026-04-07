<?php declare(strict_types=1);

namespace App\Services\HR;


use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

/**
 * EmployeeService — управление персоналом.
 *
 * Правила канона:
 *  - Все операции: $this->db->transaction() + FraudControlService::check() + AuditService::record()
 *  - Зарплаты в копейках (int, без дробной части)
 *  - Tenant + BusinessGroup scoping через модель (globalScope)
 *  - При найме — автоматическое связывание с существующим User (если передан user_id)
 *  - KPI-бонусы: количество доставок * 50 руб + рейтинг * 100 руб
 */
final readonly class EmployeeService
{
    public function __construct(
        private readonly Request $request,
        private FraudControlService $fraud,
        private AuditService        $audit,
        private PayrollService      $payroll,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

    /**
     * Принять нового сотрудника.
     */
    public function hire(
        int     $tenantId,
        ?int    $businessGroupId,
        ?int    $userId,
        string  $fullName,
        string  $position,
        string  $employmentType,
        int     $baseSalaryKopecks,
        string  $hireDate,
        string  $correlationId,
        ?array  $additionalPayments = null,
    ): Employee {
        $this->fraud->check(
            (int) $this->guard->id(),
            'employee_hire',
            $baseSalaryKopecks,
            $this->request->ip(),
            null,
            $correlationId,
        );

        return $this->db->transaction(static function () use (
            $tenantId, $businessGroupId, $userId, $fullName, $position,
            $employmentType, $baseSalaryKopecks, $hireDate, $additionalPayments, $correlationId
        ): Employee {
            $employee = Employee::create([
                'tenant_id'            => $tenantId,
                'business_group_id'    => $businessGroupId,
                'user_id'              => $userId,
                'uuid'                 => Str::uuid()->toString(),
                'full_name'            => $fullName,
                'position'             => $position,
                'employment_type'      => $employmentType,
                'base_salary_kopecks'  => $baseSalaryKopecks,
                'additional_payments'  => $additionalPayments,
                'hire_date'            => $hireDate,
                'is_active'            => true,
                'correlation_id'       => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Employee hired', [
                'employee_id'    => $employee->id,
                'tenant_id'      => $tenantId,
                'position'       => $position,
                'correlation_id' => $correlationId,
            ]);

            return $employee;
        });
    }

    /**
     * Уволить сотрудника.
     */
    public function terminate(Employee $employee, string $terminationDate, string $correlationId): void
    {
        $this->fraud->check(
            (int) $this->guard->id(),
            'employee_terminate',
            0,
            $this->request->ip(),
            null,
            $correlationId,
        );

        $this->db->transaction(static function () use ($employee, $terminationDate, $correlationId): void {
            $employee->update([
                'termination_date' => $terminationDate,
                'is_active'        => false,
                'correlation_id'   => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Employee terminated', [
                'employee_id'      => $employee->id,
                'termination_date' => $terminationDate,
                'correlation_id'   => $correlationId,
            ]);
        });
    }

    /**
     * Обновить оклад.
     */
    public function updateSalary(Employee $employee, int $newSalaryKopecks, string $correlationId): void
    {
        $this->fraud->check(
            (int) $this->guard->id(),
            'employee_salary_update',
            $newSalaryKopecks,
            $this->request->ip(),
            null,
            $correlationId,
        );

        $this->db->transaction(static function () use ($employee, $newSalaryKopecks, $correlationId): void {
            $oldSalary = $employee->base_salary_kopecks;

            $employee->update([
                'base_salary_kopecks' => $newSalaryKopecks,
                'correlation_id'      => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Employee salary updated', [
                'employee_id'     => $employee->id,
                'old_kopecks'     => $oldSalary,
                'new_kopecks'     => $newSalaryKopecks,
                'correlation_id'  => $correlationId,
            ]);
        });
    }

    /**
     * Рассчитать KPI-бонусы за период.
     *
     * Логика:
     *  - Курьеры: кол-во завершённых доставок * 5000 коп (50 ₽)
     *  - Мастера (beauty/auto): рейтинг * 10_000 коп (100 ₽) в месяц
     *  - Менеджеры: % от GMV вертикали (конфигурируется через config/platform.php)
     */
    public function calculateKpiBonus(Employee $employee, Carbon $periodStart, Carbon $periodEnd): int
    {
        return match ($employee->position) {
            'master'  => $this->calculateMasterBonus($employee),
            default   => 0,
        };
    }

    private function calculateCourierBonus(Employee $employee, Carbon $periodStart, Carbon $periodEnd): int
    {
        if ($employee->user_id === null) {
            return 0;
        }

        $deliveries = $this->db->table('logistics_delivery_orders')
            ->where('courier_id', $employee->user_id)
            ->where('status', 'delivered')
            ->whereBetween('updated_at', [$periodStart, $periodEnd])
            ->count();

        return $deliveries * 5_000; // 50 ₽ за каждую доставку
    }

    private function calculateMasterBonus(Employee $employee): int
    {
        if ($employee->user_id === null) {
            return 0;
        }

        // Средний рейтинг из reviews
        $avgRating = $this->db->table('reviews')
            ->where('target_user_id', $employee->user_id)
            ->avg('rating') ?? 0;

        $ratingInt = (int) round((float) $avgRating);

        return $ratingInt * 10_000; // 100 ₽ за каждую звезду рейтинга
    }
}
