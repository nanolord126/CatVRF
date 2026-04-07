<?php declare(strict_types=1);

namespace App\Services;

use App\Services\Security\RateLimiterService;
use Exception;


use Illuminate\Support\Str;
use Throwable;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class HRService
{

    public function __construct(
            private readonly FraudControlService $fraud,
            private readonly RateLimiterService $rateLimiter,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
    ) {}

        /**
         * Создаёт сотрудника для тенанта.
         *
         * @param int $tenantId ID тенанта
         * @param array $data {name, email, phone, role, salary_monthly_copeki}
         * @param string $correlationId Идентификатор корреляции
         * @return array{employeeId: int, status: string}
         * @throws Exception
         */
        public function createEmployee(
            int $tenantId,
            array $data,
            string $correlationId = '',
        ): array {
            $correlationId = $correlationId ?: (string) Str::uuid()->toString();

            try {
                // Фрод-проверка
                $this->fraud->check('employee_create', [
                    'tenant_id' => $tenantId,
                    'email' => $data['email'] ?? '',
                ], $correlationId);

                // Rate limiting
                if (!$this->rateLimiter->allowTenant($tenantId, 'employee:create', 100, 60)) {
                    throw new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException(message: 'Rate limit exceeded for employee creation');
                }

                $this->logger->channel('audit')->info('Employee creation started', [
                    'tenant_id' => $tenantId,
                    'name' => $data['name'] ?? '',
                    'correlation_id' => $correlationId,
                ]);

                $result = $this->db->transaction(function () use ($tenantId, $data, $correlationId) {
                    $employee = Employee::create([
                        'tenant_id' => $tenantId,
                        'uuid' => Str::uuid()->toString(),
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'] ?? null,
                        'role' => $data['role'] ?? 'employee',
                        'salary_monthly_copeki' => (int) ($data['salary_monthly_copeki'] ?? 0),
                        'correlation_id' => $correlationId,
                        'is_active' => true,
                        'tags' => $data['tags'] ?? ['hr:new'],
                    ]);

                    $this->logger->channel('audit')->info('Employee created successfully', [
                        'tenant_id' => $tenantId,
                        'employee_id' => $employee->id,
                        'correlation_id' => $correlationId,
                        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
                    ]);

                    return [
                        'employeeId' => $employee->id,
                        'status' => 'active',
                    ];
                });

                return $result;
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Employee creation failed', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        /**
         * Рассчитывает и начисляет зарплату сотрудникам.
         *
         * @param int $tenantId ID тенанта
         * @param int $year Год
         * @param int $month Месяц
         * @param string $correlationId Идентификатор корреляции
         * @return array{totalAmount: int, employeeCount: int, status: string}
         * @throws Exception
         */
        public function calculateAndPaySalaries(
            int $tenantId,
            int $year,
            int $month,
            string $correlationId = '',
        ): array {
            $correlationId = $correlationId ?: (string) Str::uuid()->toString();

            try {
                // Фрод-проверка
                $this->fraud->check('salary_calculate', [
                    'tenant_id' => $tenantId,
                    'year' => $year,
                    'month' => $month,
                ], $correlationId);

                // Rate limiting
                if (!$this->rateLimiter->allowTenant($tenantId, 'salary:calculate', 10, 3600)) {
                    throw new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException(message: 'Rate limit exceeded for salary calculation');
                }

                $this->logger->channel('audit')->info('Salary calculation started', [
                    'tenant_id' => $tenantId,
                    'year' => $year,
                    'month' => $month,
                    'correlation_id' => $correlationId,
                ]);

                $result = $this->db->transaction(function () use ($tenantId, $year, $month, $correlationId) {
                    $employees = Employee::where('tenant_id', $tenantId)
                        ->where('is_active', true)
                        ->get();

                    $totalAmount = 0;
                    $employeeCount = 0;

                    foreach ($employees as $employee) {
                        $totalAmount += $employee->salary_monthly_copeki;
                        $employeeCount++;
                    }

                    $this->logger->channel('audit')->info('Salary processed', [
                        'tenant_id' => $tenantId,
                        'employee_count' => $employeeCount,
                        'total_amount' => $totalAmount,
                        'correlation_id' => $correlationId,
                    ]);

                    return [
                        'totalAmount' => $totalAmount,
                        'employeeCount' => $employeeCount,
                        'status' => 'processed',
                    ];
                });

                return $result;
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Salary calculation failed', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        /**
         * Возвращает график работы для тенанта.
         *
         * @param int $tenantId ID тенанта
         * @param string $date Дата в формате YYYY-MM-DD
         * @return array{schedule: array, employees: int}
         * @throws Exception
         */
        public function getSchedule(
            int $tenantId,
            string $date,
        ): array {
            try {
                $employees = Employee::where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->get();

                return [
                    'schedule' => $employees->map(fn($e) => [
                        'employee_id' => $e->id,
                        'name' => $e->name,
                        'role' => $e->role,
                    ])->toArray(),
                    'employees' => $employees->count(),
                ];
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Schedule request failed', [
                    'tenant_id' => $tenantId,
                    'date' => $date,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
