<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Voice Picking Service
 *
 * Manages voice-guided order picking operations:
 * - Voice instruction generation
 * - Speech recognition processing
 * - Pick task management
 * - Hands-free operation support
 * - Real-time progress tracking
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class VoicePickingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Create voice picking task
     *
     * @param  string  $orderId  Order ID
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User creating task
     * @return string Task ID
     */
    public function createVoicePickingTask(
        string $orderId,
        int $warehouseId,
        int $tenantId,
        int $userId
    ): string {
        $taskId = (string) \Illuminate\Support\Str::uuid();

        return $this->db->transaction(function () use (
            $orderId,
            $warehouseId,
            $tenantId,
            $userId,
            $taskId
        ) {
            $orderItems = $this->db->table('order_items')
                ->where('order_id', $orderId)
                ->get();

            if ($orderItems->isEmpty()) {
                throw new \RuntimeException("No items found for order: {$orderId}");
            }

            $this->db->table('voice_picking_tasks')->insert([
                'id' => $taskId,
                'order_id' => $orderId,
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'status' => 'pending',
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            foreach ($orderItems as $item) {
                $pickItemId = (string) \Illuminate\Support\Str::uuid();

                $this->db->table('voice_pick_items')->insert([
                    'id' => $pickItemId,
                    'task_id' => $taskId,
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity_requested' => $item->quantity,
                    'quantity_picked' => 0,
                    'status' => 'pending',
                    'created_at' => now(),
                ]);
            }

            $this->logAction(
                action: 'voice_picking_task_created',
                entityType: 'VoicePickingTask',
                entityId: $taskId,
                context: [
                    'order_id' => $orderId,
                    'warehouse_id' => $warehouseId,
                    'total_items' => count($orderItems),
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $taskId;
        });
    }

    /**
     * Generate voice instruction for pick item
     *
     * @param  string  $pickItemId  Pick item ID
     * @param  string  $language  Language code
     * @return array Voice instruction
     */
    public function generateVoiceInstruction(string $pickItemId, string $language = 'ru'): array
    {
        $pickItem = $this->db->table('voice_pick_items')
            ->where('id', $pickItemId)
            ->first();

        if (! $pickItem) {
            throw new \RuntimeException("Pick item not found: {$pickItemId}");
        }

        $product = $this->db->table('products')
            ->where('id', $pickItem->product_id)
            ->first();

        $location = $this->db->table('inventory_items')
            ->where('product_id', $pickItem->product_id)
            ->value('location');

        $instruction = $this->buildInstructionText(
            $product->name ?? '',
            $location ?? '',
            $pickItem->quantity_requested,
            $language
        );

        return [
            'pick_item_id' => $pickItemId,
            'instruction_text' => $instruction,
            'product_name' => $product->name ?? '',
            'location' => $location,
            'quantity' => $pickItem->quantity_requested,
            'language' => $language,
            'audio_url' => $this->generateAudioUrl($instruction, $language),
        ];
    }

    /**
     * Process voice confirmation
     *
     * @param  string  $pickItemId  Pick item ID
     * @param  string  $spokenText  Spoken text from user
     * @param  int  $quantityConfirmed  Confirmed quantity
     * @param  int  $userId  User confirming
     * @return bool
     */
    public function processVoiceConfirmation(
        string $pickItemId,
        string $spokenText,
        int $quantityConfirmed,
        int $userId
    ): bool {
        return $this->db->transaction(function () use (
            $pickItemId,
            $spokenText,
            $quantityConfirmed,
            $userId
        ) {
            $pickItem = $this->db->table('voice_pick_items')
                ->where('id', $pickItemId)
                ->lockForUpdate()
                ->first();

            if (! $pickItem) {
                throw new \RuntimeException("Pick item not found: {$pickItemId}");
            }

            $isConfirmed = $this->analyzeConfirmation($spokenText);

            if ($isConfirmed) {
                $this->db->table('voice_pick_items')
                    ->where('id', $pickItemId)
                    ->update([
                        'quantity_picked' => $quantityConfirmed,
                        'status' => 'completed',
                        'confirmed_by' => $userId,
                        'confirmed_at' => now(),
                        'confirmation_text' => $spokenText,
                    ]);

                $this->updateTaskProgress($pickItem->task_id);

                $this->logAction(
                    action: 'voice_pick_confirmed',
                    entityType: 'VoicePickItem',
                    entityId: $pickItemId,
                    context: [
                        'quantity_confirmed' => $quantityConfirmed,
                        'confirmation_text' => $spokenText,
                    ],
                    userId: $userId,
                    tenantId: 0
                );
            } else {
                $this->db->table('voice_pick_items')
                    ->where('id', $pickItemId)
                    ->update([
                        'status' => 'retry',
                        'confirmation_text' => $spokenText,
                    ]);
            }

            return $isConfirmed;
        });
    }

    /**
     * Report picking issue via voice
     *
     * @param  string  $pickItemId  Pick item ID
     * @param  string  $issueType  Issue type (shortage, damaged, wrong_location)
     * @param  string  $spokenDescription  Spoken description
     * @param  int  $userId  User reporting
     * @return bool
     */
    public function reportVoiceIssue(
        string $pickItemId,
        string $issueType,
        string $spokenDescription,
        int $userId
    ): bool {
        $pickItem = $this->db->table('voice_pick_items')
            ->where('id', $pickItemId)
            ->first();

        if (! $pickItem) {
            throw new \RuntimeException("Pick item not found: {$pickItemId}");
        }

        $this->db->table('voice_pick_issues')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'pick_item_id' => $pickItemId,
            'task_id' => $pickItem->task_id,
            'issue_type' => $issueType,
            'description' => $spokenDescription,
            'reported_by' => $userId,
            'reported_at' => now(),
        ]);

        $this->db->table('voice_pick_items')
            ->where('id', $pickItemId)
            ->update([
                'status' => 'issue_reported',
            ]);

        $this->logAction(
            action: 'voice_pick_issue_reported',
            entityType: 'VoicePickItem',
            entityId: $pickItemId,
            context: [
                'issue_type' => $issueType,
                'description' => $spokenDescription,
            ],
            userId: $userId,
            tenantId: 0
        );

        return true;
    }

    /**
     * Get next pick item for task
     *
     * @param  string  $taskId  Task ID
     * @return array|null Next pick item
     */
    public function getNextPickItem(string $taskId): ?array
    {
        $pickItem = $this->db->table('voice_pick_items')
            ->where('task_id', $taskId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->first();

        if (! $pickItem) {
            return null;
        }

        return [
            'pick_item_id' => $pickItem->id,
            'product_id' => $pickItem->product_id,
            'quantity_requested' => $pickItem->quantity_requested,
            'status' => $pickItem->status,
        ];
    }

    /**
     * Complete voice picking task
     *
     * @param  string  $taskId  Task ID
     * @param  int  $userId  User completing
     * @return bool
     */
    public function completeVoicePickingTask(string $taskId, int $userId): bool
    {
        return $this->db->transaction(function () use ($taskId, $userId) {
            $task = $this->db->table('voice_picking_tasks')
                ->where('id', $taskId)
                ->lockForUpdate()
                ->first();

            if (! $task) {
                throw new \RuntimeException("Task not found: {$taskId}");
            }

            $pickItems = $this->db->table('voice_pick_items')
                ->where('task_id', $taskId)
                ->get();

            $completedCount = $pickItems->where('status', 'completed')->count();
            $totalCount = $pickItems->count();

            if ($completedCount < $totalCount) {
                throw new \RuntimeException("Cannot complete task: {$completedCount}/{$totalCount} items completed");
            }

            $this->db->table('voice_picking_tasks')
                ->where('id', $taskId)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'completed_by' => $userId,
                ]);

            $this->logAction(
                action: 'voice_picking_task_completed',
                entityType: 'VoicePickingTask',
                entityId: $taskId,
                context: [
                    'total_items' => $totalCount,
                    'completed_items' => $completedCount,
                ],
                userId: $userId,
                tenantId: $task->tenant_id
            );

            return true;
        });
    }

    /**
     * Get task progress
     *
     * @param  string  $taskId  Task ID
     * @return array Progress data
     */
    public function getTaskProgress(string $taskId): array
    {
        $task = $this->db->table('voice_picking_tasks')
            ->where('id', $taskId)
            ->first();

        if (! $task) {
            throw new \RuntimeException("Task not found: {$taskId}");
        }

        $pickItems = $this->db->table('voice_pick_items')
            ->where('task_id', $taskId)
            ->get();

        $statusCounts = $pickItems->groupBy('status')->map->count()->toArray();

        return [
            'task_id' => $taskId,
            'status' => $task->status,
            'total_items' => $pickItems->count(),
            'completed' => $statusCounts['completed'] ?? 0,
            'pending' => $statusCounts['pending'] ?? 0,
            'retry' => $statusCounts['retry'] ?? 0,
            'issue_reported' => $statusCounts['issue_reported'] ?? 0,
            'progress_percentage' => $pickItems->count() > 0
                ? (($statusCounts['completed'] ?? 0) / $pickItems->count()) * 100
                : 0,
        ];
    }

    /**
     * Build instruction text
     *
     * @param  string  $productName  Product name
     * @param  string  $location  Location
     * @param  int  $quantity  Quantity
     * @param  string  $language  Language
     * @return string Instruction text
     */
    private function buildInstructionText(string $productName, string $location, int $quantity, string $language): string
    {
        return match ($language) {
            'ru' => "Перейдите к локации {$location}. Возьмите {$quantity} единиц товара {$productName}.",
            'en' => "Go to location {$location}. Pick {$quantity} units of {$productName}.",
            default => "Go to location {$location}. Pick {$quantity} units of {$productName}.",
        };
    }

    /**
     * Generate audio URL for TTS
     *
     * @param  string  $text  Text to convert
     * @param  string  $language  Language
     * @return string Audio URL
     */
    private function generateAudioUrl(string $text, string $language): string
    {
        $textHash = md5($text);
        return "/api/voice/tts?text=" . urlencode($text) . "&lang={$language}&hash={$textHash}";
    }

    /**
     * Analyze voice confirmation
     *
     * @param  string  $spokenText  Spoken text
     * @return bool Is confirmed
     */
    private function analyzeConfirmation(string $spokenText): bool
    {
        $positiveKeywords = ['да', 'yes', 'подтверждаю', 'confirm', 'правильно', 'correct', 'готово', 'done'];
        $negativeKeywords = ['нет', 'no', 'отмена', 'cancel', 'ошибка', 'error', 'неправильно', 'incorrect'];

        $lowerText = strtolower($spokenText);

        foreach ($positiveKeywords as $keyword) {
            if (str_contains($lowerText, $keyword)) {
                return true;
            }
        }

        foreach ($negativeKeywords as $keyword) {
            if (str_contains($lowerText, $keyword)) {
                return false;
            }
        }

        return false;
    }

    /**
     * Update task progress
     *
     * @param  string  $taskId  Task ID
     * @return void
     */
    private function updateTaskProgress(string $taskId): void
    {
        $pickItems = $this->db->table('voice_pick_items')
            ->where('task_id', $taskId)
            ->get();

        $completedCount = $pickItems->where('status', 'completed')->count();
        $totalCount = $pickItems->count();

        if ($completedCount === $totalCount) {
            $this->db->table('voice_picking_tasks')
                ->where('id', $taskId)
                ->update([
                    'status' => 'ready_for_completion',
                ]);
        }
    }
}
