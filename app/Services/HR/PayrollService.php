<?php declare(strict_types=1);

namespace App\Services\HR;


use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

/**
 * PayrollService — начисление и выплата зарплат.
 *
 * Правила канона:
 *  - Зарплаты только через WalletService (debit tenant → credit employee)
 *  - Только approved расчётные листы могут быть выплачены
 *  - Fraud-check перед каждой выплатой (payload = total_kopecks)
 *  - Все суммы в копейках (int)
 *  - После выплаты — статус 'paid', обратный откат невозможен
 */
final readonly class PayrollService
{
    public function __construct(
        private readonly Request $request,
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private EmployeeService     $employeeService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

    /**
     * Сформировать расчётный лист за период (статус draft).
     */
    public function calculate(
        Employee $employee,
        Carbon   $periodStart,
        Carbon   $periodEnd,
        string   $correlationId,
    ): Payroll {
        $this->fraud->check(
            (int) $this->guard->id(),
            'payroll_calculate',
            (int) $employee->base_salary_kopecks,
            $this->request->ip(),
            null,
            $correlationId,
        );

        return $this->db->transaction(function () use ($employee, $periodStart, $periodEnd, $correlationId): Payroll {
            $bonuses = $this->employeeService->calculateKpiBonus($employee, $periodStart, $periodEnd);

            $payroll = Payroll::create([
                'uuid'                 => Str::uuid()->toString(),
                'tenant_id'            => $employee->tenant_id,
                'employee_id'          => $employee->id,
                'period_start'         => $periodStart->toDateString(),
                'period_end'           => $periodEnd->toDateString(),
                'base_salary_kopecks'  => $employee->base_salary_kopecks,
                'bonuses_kopecks'      => $bonuses,
                'deductions_kopecks'   => 0,
                'status'               => 'draft',
                'correlation_id'       => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Payroll calculated', [
                'payroll_id'      => $payroll->id,
                'employee_id'     => $employee->id,
                'total_kopecks'   => $payroll->total_kopecks,
                'correlation_id'  => $correlationId,
            ]);

            return $payroll;
        });
    }

    /**
     * Утвердить расчётный лист (draft → approved).
     * Утвердить может только менеджер или владелец.
     */
    public function approve(Payroll $payroll, string $correlationId): void
    {
        if ($payroll->status !== 'draft') {
            throw new \DomainException("Payroll #{$payroll->id} is not in draft status");
        }

        $this->db->transaction(static function () use ($payroll, $correlationId): void {
            $payroll->update([
                'status'         => 'approved',
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Payroll approved', [
                'payroll_id'     => $payroll->id,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Выплатить зарплату (approved → paid).
     *
     * Поток:
     *   1. Fraud-check (сумма total_kopecks)
     *   2. WalletService::debit() с кошелька tenant
     *   3. WalletService::credit() на кошелёк сотрудника
     *   4. Статус → paid
     */
    public function pay(Payroll $payroll, string $correlationId): void
    {
        if ($payroll->status !== 'approved') {
            throw new \DomainException("Payroll #{$payroll->id} must be approved before payment");
        }

        $totalKopecks = (int) $payroll->total_kopecks;

        $this->fraud->check(
            (int) $this->guard->id(),
            'payroll_pay',
            $totalKopecks,
            $this->request->ip(),
            null,
            $correlationId,
        );

        $this->db->transaction(function () use ($payroll, $totalKopecks, $correlationId): void {
            $employee = $payroll->employee()->with('user')->firstOrFail();

            // Кошелёк tenant (работодатель)
            $tenantWalletId = (int) $this->db->table('wallets')
                ->where('tenant_id', $payroll->tenant_id)
                ->whereNull('user_id')
                ->value('id');

            if ($tenantWalletId === 0) {
                throw new \DomainException("Tenant wallet not found for payroll #{$payroll->id}");
            }

            // Кошелёк сотрудника (user wallet)
            $employeeWalletId = null;
            if ($employee->user_id !== null) {
                $employeeWalletId = $this->db->table('wallets')
                    ->where('user_id', $employee->user_id)
                    ->value('id');
            }

            // Списание с tenant
            $debited = $this->wallet->debit($tenantWalletId, $totalKopecks, \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL, $correlationId, null, null, null);

            if (!$debited) {
                throw new \DomainException("Insufficient funds on tenant wallet for payroll #{$payroll->id}");
            }

            // Зачисление сотруднику (если у него есть кошелёк на платформе)
            if ($employeeWalletId !== null) {
                $this->wallet->credit((int) $employeeWalletId, $totalKopecks, \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, [
                    'payroll_id'       => $payroll->id,
                    'correlation_id' => $correlationId,
                ]);
            }
        });
    }

    /**
     * Массовая выплата зарплат всем сотрудникам tenant за период.
     * Используется в BatchPayrollJob.
     */
    public function payAll(int $tenantId, Carbon $periodStart, Carbon $periodEnd, string $correlationId): array
    {
        $payrolls = Payroll::where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereBetween('period_start', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get();

        $paid   = 0;
        $failed = 0;

        foreach ($payrolls as $payroll) {
            try {
                $this->pay($payroll, $correlationId);
                $paid++;
            } catch (\Throwable $e) {
                $failed++;
                $this->logger->channel('audit')->error('Payroll batch pay failed', [
                    'payroll_id'     => $payroll->id,
                    'error'          => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        return ['paid' => $paid, 'failed' => $failed, 'total' => $payrolls->count()];
    }
}
