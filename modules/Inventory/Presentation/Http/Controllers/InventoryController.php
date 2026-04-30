<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Application\Services\FIFOShelfLifeService;
use Modules\Inventory\Domain\Repositories\InventoryItemRepositoryInterface;
use Modules\Inventory\Domain\Repositories\InventoryBatchRepositoryInterface;
use Modules\Inventory\Domain\Entities\InventoryItem;
use Psr\Log\LoggerInterface;

final readonly class InventoryController
{
    public function __construct(
        private FIFOShelfLifeService $fifoShelfLifeService,
        private InventoryItemRepositoryInterface $itemRepository,
        private InventoryBatchRepositoryInterface $batchRepository,
        private LoggerInterface $logger,
    ) {}

    public function autoDeduct(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'item_id' => 'required|integer',
                'quantity' => 'required|integer|min:1',
                'context' => 'required|string|in:sale,prescription,kitchen,grooming',
                'meta' => 'nullable|array',
            ]);

            $item = $this->itemRepository->findById((int) $request->input('item_id'));
            if (!$item) {
                return new JsonResponse(['error' => 'Inventory item not found'], 404);
            }

            $result = $this->fifoShelfLifeService->autoDeduct(
                item: $item,
                quantity: (int) $request->input('quantity'),
                context: $request->input('context'),
                meta: $request->input('meta', [])
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Inventory deducted successfully',
                'result' => [
                    'total_quantity' => $result->totalQuantity,
                    'context' => $result->context,
                    'deducted_batches' => $result->deductedBatches->toArray(),
                    'meta' => $result->meta,
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Inventory auto-deduct failed', [
                'item_id' => $request->input('item_id'),
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function validateBeforeSale(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'item_id' => 'required|integer',
                'quantity' => 'required|integer|min:1',
            ]);

            $item = $this->itemRepository->findById((int) $request->input('item_id'));
            if (!$item) {
                return new JsonResponse(['error' => 'Inventory item not found'], 404);
            }

            $this->fifoShelfLifeService->validateBeforeSale(
                item: $item,
                quantity: (int) $request->input('quantity')
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Inventory validation passed',
                'item' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'is_controlled' => $item->isControlled,
                    'expiry_date' => $item->expiryDate?->format('Y-m-d'),
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Inventory validation failed', [
                'item_id' => $request->input('item_id'),
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function getNextExpiringBatch(Request $request, int $itemId): JsonResponse
    {
        try {
            $batch = $this->fifoShelfLifeService->getNextExpiringBatch($itemId);

            if (!$batch) {
                return new JsonResponse(['error' => 'No expiring batch found'], 404);
            }

            return new JsonResponse([
                'success' => true,
                'batch' => [
                    'id' => $batch->id,
                    'batch_number' => $batch->batchNumber,
                    'expiry_date' => $batch->expiryDate->format('Y-m-d'),
                    'current_quantity' => $batch->currentQuantity,
                    'status' => $batch->status,
                    'days_until_expiry' => $batch->getDaysUntilExpiry(),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Next expiring batch retrieval failed', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function getItem(Request $request, int $itemId): JsonResponse
    {
        try {
            $item = $this->itemRepository->findById($itemId);
            if (!$item) {
                return new JsonResponse(['error' => 'Inventory item not found'], 404);
            }

            return new JsonResponse([
                'success' => true,
                'item' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'is_controlled' => $item->isControlled,
                    'expiry_date' => $item->expiryDate?->format('Y-m-d'),
                    'status' => $item->status->value,
                    'tenant_id' => $item->tenantId,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Inventory item retrieval failed', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function getItemBatches(Request $request, int $itemId): JsonResponse
    {
        try {
            $batches = $this->batchRepository->findByItemId($itemId);

            return new JsonResponse([
                'success' => true,
                'batches' => array_map(fn ($b) => [
                    'id' => $b->id,
                    'batch_number' => $b->batchNumber,
                    'expiry_date' => $b->expiryDate->format('Y-m-d'),
                    'manufacture_date' => $b->manufactureDate->format('Y-m-d'),
                    'current_quantity' => $b->currentQuantity,
                    'initial_quantity' => $b->initialQuantity,
                    'status' => $b->status,
                    'days_until_expiry' => $b->getDaysUntilExpiry(),
                ], $batches),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Item batches retrieval failed', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function getExpiringItems(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'days' => 'nullable|integer|min:1|max:365',
                'tenant_id' => 'nullable|integer',
            ]);

            $days = $request->input('days', 30);
            $tenantId = $request->input('tenant_id');

            $items = $this->itemRepository->findExpiringWithinDays($days, $tenantId);

            return new JsonResponse([
                'success' => true,
                'items' => array_map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'sku' => $i->sku,
                    'quantity' => $i->quantity,
                    'expiry_date' => $i->expiryDate?->format('Y-m-d'),
                    'days_until_expiry' => $i->getDaysUntilExpiry(),
                    'status' => $i->status->value,
                ], $items),
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Expiring items retrieval failed', [
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function dailyMaintenance(Request $request): JsonResponse
    {
        try {
            $this->fifoShelfLifeService->dailyMaintenance();

            return new JsonResponse([
                'success' => true,
                'message' => 'Daily maintenance completed successfully',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Daily maintenance failed', [
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }
}
