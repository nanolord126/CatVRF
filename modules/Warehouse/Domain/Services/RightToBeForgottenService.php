<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Modules\Warehouse\Domain\Exceptions\PIIProtectionException;
use Psr\Log\LoggerInterface;

/**
 * Right to Be Forgotten Service for 152-ФЗ compliance
 * 
 * Сервис обеспечивает удаление персональных данных по требованию субъекта:
 * - Полное удаление PII из всех таблиц
 * - Анонимизация исторических данных
 * - Логирование запросов на удаление
 * - Подтверждение удаления
 */
final readonly class RightToBeForgottenService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly PIIProtectionService $piiService,
        private readonly EncryptionService $encryptionService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Обработка запроса на удаление PII субъекта
     */
    public function processDeletionRequest(int $userId, string $requestReason, int $approverId): array
    {
        $this->db->transaction(function () use ($userId, $requestReason, $approverId) {
            // Логирование запроса на удаление
            $this->logDeletionRequest($userId, $requestReason, $approverId);

            // Удаление PII из всех связанных таблиц
            $deletedRecords = $this->deletePIIForUser($userId);

            // Анонимизация исторических данных
            $anonymizedRecords = $this->anonymizeHistoricalData($userId);

            // Логирование завершения удаления
            $this->logger->info('Right to be forgotten request completed', [
                'user_id' => $userId,
                'deleted_records' => $deletedRecords,
                'anonymized_records' => $anonymizedRecords,
                'approver_id' => $approverId,
            ]);

            return [
                'user_id' => $userId,
                'deleted_records' => $deletedRecords,
                'anonymized_records' => $anonymizedRecords,
                'completed_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Удаление PII для пользователя из всех таблиц
     */
    private function deletePIIForUser(int $userId): array
    {
        $deletedRecords = [];

        // Удаление из inventory_counts (performed_by, approved_by)
        $deletedRecords['inventory_counts'] = $this->db->table('warehouse_inventory_counts')
            ->where('performed_by', $userId)
            ->orWhere('approved_by', $userId)
            ->update([
                'performed_by' => null,
                'approved_by' => null,
                'notes' => $this->db->raw("COALESCE(CONCAT(notes, ' [PII deleted per 152-FZ]'), '[PII deleted per 152-FZ]')")
            ]);

        // Удаление из stock_movements (reason, notes с PII)
        $deletedRecords['stock_movements'] = $this->db->table('warehouse_stock_movements')
            ->where('user_id', $userId)
            ->update([
                'reason' => '[PII deleted per 152-FZ]',
                'notes' => '[PII deleted per 152-FZ]',
                'metadata' => $this->db->raw("JSON_SET(COALESCE(metadata, '{}'), '$.pii_deleted', true)")
            ]);

        // Удаление из controlled_substances_log
        $deletedRecords['controlled_substances_log'] = $this->db->table('warehouse_controlled_substances_log')
            ->where('user_id', $userId)
            ->orWhere('second_user_id', $userId)
            ->update([
                'reason' => '[PII deleted per 152-FZ]',
                'recipient_name' => null,
                'recipient_inn' => null,
            ]);

        // Удаление из documents (created_by, approved_by)
        $deletedRecords['documents'] = $this->db->table('warehouse_documents')
            ->where('created_by', $userId)
            ->orWhere('approved_by', $userId)
            ->update([
                'created_by' => null,
                'approved_by' => null,
                'notes' => $this->db->raw("COALESCE(CONCAT(notes, ' [PII deleted per 152-FZ]'), '[PII deleted per 152-FZ]')")
            ]);

        return $deletedRecords;
    }

    /**
     * Анонимизация исторических данных, которые нельзя удалить полностью
     */
    private function anonymizeHistoricalData(int $userId): array
    {
        $anonymizedRecords = [];

        // Анонимизация в audit trail (если используется)
        $anonymizedRecords['audit_logs'] = $this->db->table('audit_logs')
            ->where('user_id', $userId)
            ->update([
                'user_name' => '[ANONYMIZED]',
                'context' => $this->db->raw("JSON_SET(context, '$.pii_anonymized', true)")
            ]);

        return $anonymizedRecords;
    }

    /**
     * Проверка наличия PII для пользователя
     */
    public function checkPIIExistence(int $userId): array
    {
        $piiLocations = [];

        // Проверка в inventory_counts
        $piiLocations['inventory_counts'] = $this->db->table('warehouse_inventory_counts')
            ->where('performed_by', $userId)
            ->orWhere('approved_by', $userId)
            ->count();

        // Проверка в stock_movements
        $piiLocations['stock_movements'] = $this->db->table('warehouse_stock_movements')
            ->where('user_id', $userId)
            ->count();

        // Проверка в controlled_substances_log
        $piiLocations['controlled_substances_log'] = $this->db->table('warehouse_controlled_substances_log')
            ->where('user_id', $userId)
            ->orWhere('second_user_id', $userId)
            ->count();

        // Проверка в documents
        $piiLocations['documents'] = $this->db->table('warehouse_documents')
            ->where('created_by', $userId)
            ->orWhere('approved_by', $userId)
            ->count();

        return $piiLocations;
    }

    /**
     * Логирование запроса на удаление
     */
    private function logDeletionRequest(int $userId, string $reason, int $approverId): void
    {
        $this->db->table('pii_deletion_requests')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $userId,
            'requested_by' => $userId,
            'approved_by' => $approverId,
            'reason' => $reason,
            'status' => 'processing',
            'created_at' => now(),
            'approved_at' => now(),
        ]);

        $this->logger->warning('Right to be forgotten request initiated', [
            'user_id' => $userId,
            'reason' => $reason,
            'approver_id' => $approverId,
        ]);
    }

    /**
     * Создание отчета об удалении PII
     */
    public function generateDeletionReport(int $userId): array
    {
        $piiExistence = $this->checkPIIExistence($userId);
        $hasPII = array_sum($piiExistence) > 0;

        return [
            'user_id' => $userId,
            'has_pii' => $hasPII,
            'pii_locations' => $piiExistence,
            'total_records' => array_sum($piiExistence),
            'generated_at' => now()->toIso8601String(),
            'compliance_note' => $hasPII 
                ? 'PII found and requires deletion per 152-FZ Article 10' 
                : 'No PII found for this user',
        ];
    }

    /**
     * Частичное удаление PII (для случаев, когда полное удаление невозможно)
     */
    public function partialPIIDeletion(int $userId, array $tablesToProcess): array
    {
        $results = [];

        foreach ($tablesToProcess as $table) {
            $results[$table] = match ($table) {
                'inventory_counts' => $this->db->table('warehouse_inventory_counts')
                    ->where('performed_by', $userId)
                    ->orWhere('approved_by', $userId)
                    ->update([
                        'performed_by' => null,
                        'approved_by' => null,
                    ]),
                'stock_movements' => $this->db->table('warehouse_stock_movements')
                    ->where('user_id', $userId)
                    ->update([
                        'reason' => '[PII deleted]',
                        'notes' => '[PII deleted]',
                    ]),
                default => 0,
            };
        }

        $this->logger->info('Partial PII deletion completed', [
            'user_id' => $userId,
            'tables_processed' => $tablesToProcess,
            'results' => $results,
        ]);

        return $results;
    }
}
