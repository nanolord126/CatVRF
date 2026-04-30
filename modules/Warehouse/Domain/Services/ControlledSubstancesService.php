<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Modules\Warehouse\Domain\Exceptions\ControlledSubstancesException;

/**
 * Controlled Substances Service for narcotics and psychotropics management
 * 
 * Сервис обеспечивает контроль наркотических и психотропных веществ:
 * - Специальный учет
 * - Двухуровневый контроль доступа
 * - Обязательное документирование
 * - Интеграция с ФСБ
 */
final readonly class ControlledSubstancesService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {}
    private const LIST_I = 'list_i'; // Наркотические средства
    private const LIST_II = 'list_ii'; // Психотропные вещества
    private const LIST_III = 'list_iii'; // Прекурсоры

    /**
     * Проверка на контролируемое вещество
     */
    public function isControlledSubstance(string $productCategory): bool
    {
        $controlledCategories = [
            'narcotic',
            'psychotropic',
            'precursor',
            'controlled',
            'list_i',
            'list_ii',
            'list_iii'
        ];

        $category = strtolower($productCategory);

        foreach ($controlledCategories as $controlledCategory) {
            if (str_contains($category, $controlledCategory)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Определение списка контролируемых веществ
     */
    public function determineControlledList(string $productCategory): string
    {
        $category = strtolower($productCategory);

        if (str_contains($category, 'narcotic') || str_contains($category, 'list_i')) {
            return self::LIST_I;
        }

        if (str_contains($category, 'psychotropic') || str_contains($category, 'list_ii')) {
            return self::LIST_II;
        }

        if (str_contains($category, 'precursor') || str_contains($category, 'list_iii')) {
            return self::LIST_III;
        }

        throw ControlledSubstancesException::unknownCategory($productCategory);
    }

    /**
     * Проверка двойного контроля для операций с контролируемыми веществами
     */
    public function requireDualControl(string $controlledList): bool
    {
        // Для Списка I и II требуется двойной контроль
        return in_array($controlledList, [self::LIST_I, self::LIST_II], true);
    }

    /**
     * Проверка доступа к контролируемым веществам
     */
    public function checkAccessPermission(int $userId, string $role, string $controlledList): bool
    {
        // Только менеджеры и старший персонал могут работать с Списком I
        if ($controlledList === self::LIST_I) {
            return in_array($role, ['manager', 'senior_manager', 'admin'], true);
        }

        // Для Списка II и III требуется специальное разрешение
        if (in_array($controlledList, [self::LIST_II, self::LIST_III], true)) {
            return in_array($role, ['manager', 'senior_manager', 'admin', 'pharmacist'], true);
        }

        return false;
    }

    /**
     * Логирование операции с контролируемым веществом
     */
    public function logControlledOperation(
        int $userId,
        int $secondUserId, // Второй сотрудник для двойного контроля
        string $operation,
        string $productId,
        int $quantity,
        string $reason
    ): void {
        $this->db->table('warehouse_controlled_substances_log')->insert([
            'user_id' => $userId,
            'second_user_id' => $secondUserId,
            'operation' => $operation,
            'product_id' => $productId,
            'quantity' => $quantity,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        $this->logger->info('Controlled substance operation', [
            'user_id' => $userId,
            'second_user_id' => $secondUserId,
            'operation' => $operation,
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Проверка дневного лимита для сотрудника
     */
    public function checkDailyLimit(int $userId, string $productId, int $requestedQuantity): bool
    {
        // TODO: Получить лимит из конфигурации или БД
        $dailyLimit = $this->getDailyLimit($userId);

        // Проверить уже выданное количество за сегодня
        $issuedToday = $this->db->table('warehouse_controlled_substances_log')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->whereDate('created_at', today())
            ->where('operation', 'issue')
            ->sum('quantity');

        return ($issuedToday + $requestedQuantity) <= $dailyLimit;
    }

    /**
     * Проверка месячного лимита для склада
     */
    public function checkMonthlyLimit(string $warehouseId, string $productId, int $requestedQuantity): bool
    {
        // TODO: Получить лимит из конфигурации или БД
        $monthlyLimit = $this->getMonthlyLimit($warehouseId);

        // Проверить уже выданное количество за месяц
        $issuedThisMonth = $this->db->table('warehouse_controlled_substances_log')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->where('operation', 'issue')
            ->sum('quantity');

        return ($issuedThisMonth + $requestedQuantity) <= $monthlyLimit;
    }

    /**
     * Генерация отчета для ФСБ
     */
    public function generateFSBReport(string $warehouseId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $operations = $this->db->table('warehouse_controlled_substances_log')
            ->where('warehouse_id', $warehouseId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        return [
            'warehouse_id' => $warehouseId,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'operations' => $operations->toArray(),
            'summary' => [
                'total_operations' => $operations->count(),
                'total_quantity' => $operations->sum('quantity'),
            ],
        ];
    }

    /**
     * Получение дневного лимита для сотрудника
     */
    private function getDailyLimit(int $userId): int
    {
        // TODO: Получить из конфигурации или БД
        return 100; // Примерное значение
    }

    /**
     * Получение месячного лимита для склада
     */
    private function getMonthlyLimit(string $warehouseId): int
    {
        // TODO: Получить из конфигурации или БД
        return 10000; // Примерное значение
    }
}
