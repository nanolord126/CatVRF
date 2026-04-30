<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Warehouse\Application\Services\WarehouseApplicationService;
use Modules\Warehouse\Application\Services\ZoneApplicationService;
use Modules\Warehouse\Application\Services\BinApplicationService;
use Modules\Warehouse\Application\Services\ProductApplicationService;
use Modules\Warehouse\Application\Services\MovementApplicationService;
use Modules\Warehouse\Domain\Enums\WarehouseTypeEnum;
use Modules\Warehouse\Domain\Enums\ZoneTypeEnum;
use Modules\Warehouse\Domain\Enums\MovementTypeEnum;

/**
 * Warehouse API Routes
 *
 * REST API endpoints for Warehouse vertical (physical space management)
 * Separate from Inventory (stock counting process)
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Warehouse endpoints
    Route::prefix('warehouses')->group(function () {
        Route::get('/', function (WarehouseApplicationService $service) {
            $tenantId = auth()->user()->tenant_id;
            return response()->json($service->getWarehousesByTenant($tenantId));
        });

        Route::get('/active', function (WarehouseApplicationService $service) {
            return response()->json($service->getActiveWarehouses());
        });

        Route::get('/{warehouseId}', function (string $warehouseId, WarehouseApplicationService $service) {
            $warehouse = $service->getWarehouse($warehouseId);
            if (!$warehouse) {
                return response()->json(['error' => 'Warehouse not found'], 404);
            }
            return response()->json($warehouse->toArray());
        });

        Route::post('/', function (WarehouseApplicationService $service) {
            $data = request()->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:500',
                'branch_id' => 'nullable|string|max:100',
                'type' => 'required|in:central,regional,local,transit,returns',
                'capacity' => 'required|integer|min:1',
            ]);

            $warehouse = $service->createWarehouse(
                name: $data['name'],
                address: $data['address'],
                branchId: $data['branch_id'] ?? null,
                type: WarehouseTypeEnum::from($data['type']),
                capacity: $data['capacity'],
                tenantId: auth()->user()->tenant_id,
                userId: auth()->id()
            );

            return response()->json($warehouse->toArray(), 201);
        });

        Route::put('/{warehouseId}/stock', function (string $warehouseId, WarehouseApplicationService $service) {
            $data = request()->validate([
                'quantity' => 'required|integer',
            ]);

            $warehouse = $service->updateStock(
                warehouseId: $warehouseId,
                quantity: $data['quantity'],
                tenantId: auth()->user()->tenant_id,
                userId: auth()->id()
            );

            return response()->json($warehouse->toArray());
        });

        Route::post('/{warehouseId}/activate', function (string $warehouseId, WarehouseApplicationService $service) {
            $warehouse = $service->activateWarehouse(
                warehouseId: $warehouseId,
                tenantId: auth()->user()->tenant_id,
                userId: auth()->id()
            );

            return response()->json($warehouse->toArray());
        });

        Route::post('/{warehouseId}/deactivate', function (string $warehouseId, WarehouseApplicationService $service) {
            $warehouse = $service->deactivateWarehouse(
                warehouseId: $warehouseId,
                tenantId: auth()->user()->tenant_id,
                userId: auth()->id()
            );

            return response()->json($warehouse->toArray());
        });

        Route::delete('/{warehouseId}', function (string $warehouseId, WarehouseApplicationService $service) {
            $service->deleteWarehouse(
                warehouseId: $warehouseId,
                tenantId: auth()->user()->tenant_id,
                userId: auth()->id()
            );

            return response()->json(null, 204);
        });
    });

    // Zone endpoints
    Route::prefix('zones')->group(function () {
        Route::get('/warehouse/{warehouseId}', function (string $warehouseId, ZoneApplicationService $service) {
            $zones = $service->getZonesByWarehouse($warehouseId);
            return response()->json(array_map(fn($z) => $z->toArray(), $zones));
        });

        Route::get('/warehouse/{warehouseId}/active', function (string $warehouseId, ZoneApplicationService $service) {
            $zones = $service->getActiveZonesByWarehouse($warehouseId);
            return response()->json(array_map(fn($z) => $z->toArray(), $zones));
        });

        Route::get('/{zoneId}', function (string $zoneId, ZoneApplicationService $service) {
            $zone = $service->getZone($zoneId);
            if (!$zone) {
                return response()->json(['error' => 'Zone not found'], 404);
            }
            return response()->json($zone->toArray());
        });

        Route::post('/', function (ZoneApplicationService $service) {
            $data = request()->validate([
                'warehouse_id' => 'required|string',
                'name' => 'required|string|max:255',
                'type' => 'required|in:receiving,storage,picking,packing,shipping,quarantine,returns',
                'capacity' => 'required|integer|min:1',
                'branch_id' => 'nullable|string|max:100',
            ]);

            $zone = $service->createZone(
                warehouseId: $data['warehouse_id'],
                name: $data['name'],
                type: ZoneTypeEnum::from($data['type']),
                capacity: $data['capacity'],
                tenantId: auth()->user()->tenant_id,
                branchId: $data['branch_id'] ?? null,
                userId: auth()->id()
            );

            return response()->json($zone->toArray(), 201);
        });

        Route::put('/{zoneId}/stock', function (string $zoneId, ZoneApplicationService $service) {
            $data = request()->validate([
                'quantity' => 'required|integer',
            ]);

            $zone = $service->updateZoneStock(
                zoneId: $zoneId,
                quantity: $data['quantity'],
                tenantId: auth()->user()->tenant_id,
                userId: auth()->id()
            );

            return response()->json($zone->toArray());
        });

        Route::delete('/{zoneId}', function (string $zoneId, ZoneApplicationService $service) {
            $service->deleteZone(
                zoneId: $zoneId,
                tenantId: auth()->user()->tenant_id,
                userId: auth()->id()
            );

            return response()->json(null, 204);
        });
    });

    // Bin endpoints
    Route::prefix('bins')->group(function () {
        Route::get('/zone/{zoneId}', function (string $zoneId, BinApplicationService $service) {
            $bins = $service->getBinsByZone($zoneId);
            return response()->json(array_map(fn($b) => $b->toArray(), $bins));
        });

        Route::get('/zone/{zoneId}/active', function (string $zoneId, BinApplicationService $service) {
            $bins = $service->getActiveBinsByZone($zoneId);
            return response()->json(array_map(fn($b) => $b->toArray(), $bins));
        });

        Route::get('/code/{code}', function (string $code, BinApplicationService $service) {
            $bin = $service->getBinByCode($code);
            if (!$bin) {
                return response()->json(['error' => 'Bin not found'], 404);
            }
            return response()->json($bin->toArray());
        });

        Route::get('/{binId}', function (string $binId, BinApplicationService $service) {
            $bin = $service->getBin($binId);
            if (!$bin) {
                return response()->json(['error' => 'Bin not found'], 404);
            }
            return response()->json($bin->toArray());
        });

        Route::post('/', function (BinApplicationService $service) {
            $data = request()->validate([
                'zone_id' => 'required|string',
                'code' => 'required|string|max:50',
                'name' => 'required|string|max:255',
                'capacity' => 'required|integer|min:1',
                'coordinates' => 'nullable|string|max:100',
            ]);

            $bin = $service->createBin(
                zoneId: $data['zone_id'],
                code: $data['code'],
                name: $data['name'],
                capacity: $data['capacity'],
                coordinates: $data['coordinates'] ?? null,
                tenantId: auth()->user()->tenant_id,
                userId: auth()->id()
            );

            return response()->json($bin->toArray(), 201);
        });

        Route::put('/{binId}/stock', function (string $binId, BinApplicationService $service) {
            $data = request()->validate([
                'quantity' => 'required|integer',
            ]);

            $bin = $service->updateBinStock(
                binId: $binId,
                quantity: $data['quantity'],
                tenantId: auth()->user()->tenant_id,
                userId: auth()->id()
            );

            return response()->json($bin->toArray());
        });

        Route::delete('/{binId}', function (string $binId, BinApplicationService $service) {
            $service->deleteBin(
                binId: $binId,
                tenantId: auth()->user()->tenant_id,
                userId: auth()->id()
            );

            return response()->json(null, 204);
        });
    });

    // Product endpoints
    Route::prefix('products')->group(function () {
        Route::get('/', function (ProductApplicationService $service) {
            $products = $service->getActiveProducts();
            return response()->json(array_map(fn($p) => $p->toArray(), $products));
        });

        Route::get('/{productId}', function (string $productId, ProductApplicationService $service) {
            $product = $service->getProduct($productId);
            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }
            return response()->json($product->toArray());
        });

        Route::get('/sku/{sku}', function (string $sku, ProductApplicationService $service) {
            $product = $service->getProductBySku($sku);
            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }
            return response()->json($product->toArray());
        });

        Route::get('/barcode/{barcode}', function (string $barcode, ProductApplicationService $service) {
            $product = $service->getProductByBarcode($barcode);
            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }
            return response()->json($product->toArray());
        });

        Route::get('/category/{category}', function (string $category, ProductApplicationService $service) {
            $products = $service->getProductsByCategory($category);
            return response()->json(array_map(fn($p) => $p->toArray(), $products));
        });

        Route::get('/search/{query}', function (string $query, ProductApplicationService $service) {
            $products = $service->searchProducts($query);
            return response()->json(array_map(fn($p) => $p->toArray(), $products));
        });

        Route::post('/', function (ProductApplicationService $service) {
            $data = request()->validate([
                'sku' => 'required|string|max:100',
                'name' => 'required|string|max:255',
                'unit' => 'string|max:20',
                'weight' => 'numeric|min:0',
                'barcode' => 'nullable|string|max:100',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'brand' => 'nullable|string|max:100',
                'is_hazardous' => 'boolean',
                'is_fragile' => 'boolean',
                'requires_temperature_control' => 'boolean',
                'min_temperature' => 'nullable|numeric',
                'max_temperature' => 'nullable|numeric',
                'dimensions' => 'nullable|array',
            ]);

            $product = $service->createProduct(
                sku: $data['sku'],
                name: $data['name'],
                tenantId: auth()->user()->tenant_id,
                unit: $data['unit'] ?? 'шт',
                weight: $data['weight'] ?? 0.0,
                barcode: $data['barcode'] ?? null,
                description: $data['description'] ?? null,
                category: $data['category'] ?? null,
                brand: $data['brand'] ?? null,
                isHazardous: $data['is_hazardous'] ?? false,
                isFragile: $data['is_fragile'] ?? false,
                requiresTemperatureControl: $data['requires_temperature_control'] ?? false,
                minTemperature: $data['min_temperature'] ?? null,
                maxTemperature: $data['max_temperature'] ?? null,
                dimensions: $data['dimensions'] ?? null,
                userId: auth()->id()
            );

            return response()->json($product->toArray(), 201);
        });

        Route::delete('/{productId}', function (string $productId, ProductApplicationService $service) {
            $service->deleteProduct(
                productId: $productId,
                tenantId: auth()->user()->tenant_id,
                userId: auth()->id()
            );

            return response()->json(null, 204);
        });
    });

    // Movement endpoints
    Route::prefix('movements')->group(function () {
        Route::get('/warehouse/{warehouseId}', function (string $warehouseId, MovementApplicationService $service) {
            $movements = $service->getMovementsByWarehouse($warehouseId);
            return response()->json(array_map(fn($m) => $m->toArray(), $movements));
        });

        Route::get('/order-type/{orderType}', function (string $orderType, MovementApplicationService $service) {
            $movements = $service->getMovementsByOrderType(\Modules\Warehouse\Domain\Enums\OrderTypeEnum::from($orderType));
            return response()->json(array_map(fn($m) => $m->toArray(), $movements));
        });

        Route::get('/movement-type/{movementType}', function (string $movementType, MovementApplicationService $service) {
            $movements = $service->getMovementsByMovementType(MovementTypeEnum::from($movementType));
            return response()->json(array_map(fn($m) => $m->toArray(), $movements));
        });

        Route::get('/inventory-item/{inventoryItemId}', function (string $inventoryItemId, MovementApplicationService $service) {
            $movements = $service->getMovementsByInventoryItem($inventoryItemId);
            return response()->json(array_map(fn($m) => $m->toArray(), $movements));
        });

        Route::get('/date-range', function (MovementApplicationService $service) {
            $data = request()->validate([
                'from' => 'required|date',
                'to' => 'required|date',
            ]);

            $movements = $service->getMovementsByDateRange(
                from: new \DateTimeImmutable($data['from']),
                to: new \DateTimeImmutable($data['to'])
            );

            return response()->json(array_map(fn($m) => $m->toArray(), $movements));
        });

        Route::get('/{movementId}', function (string $movementId, MovementApplicationService $service) {
            $movement = $service->getMovement($movementId);
            if (!$movement) {
                return response()->json(['error' => 'Movement not found'], 404);
            }
            return response()->json($movement->toArray());
        });

        Route::post('/', function (MovementApplicationService $service) {
            $data = request()->validate([
                'warehouse_id' => 'required|string',
                'from_zone_id' => 'nullable|string',
                'to_zone_id' => 'nullable|string',
                'inventory_item_id' => 'required|string',
                'product_sku' => 'required|string',
                'quantity' => 'required|integer|min:1',
                'movement_type' => 'required|in:receipt,transfer,picking,packing,shipment,return,adjustment,damage,loss,conversion',
                'order_type' => 'nullable|in:b2b,b2c',
                'order_id' => 'nullable|string',
                'branch_id' => 'nullable|string',
                'reason' => 'nullable|string',
            ]);

            $movement = $service->recordMovement(
                warehouseId: $data['warehouse_id'],
                fromZoneId: $data['from_zone_id'] ?? null,
                toZoneId: $data['to_zone_id'] ?? null,
                inventoryItemId: $data['inventory_item_id'],
                productSku: $data['product_sku'],
                quantity: $data['quantity'],
                movementType: MovementTypeEnum::from($data['movement_type']),
                tenantId: auth()->user()->tenant_id,
                orderType: $data['order_type'] ? \Modules\Warehouse\Domain\Enums\OrderTypeEnum::from($data['order_type']) : null,
                orderId: $data['order_id'] ?? null,
                branchId: $data['branch_id'] ?? null,
                reason: $data['reason'] ?? null,
                userId: auth()->id()
            );

            return response()->json($movement->toArray(), 201);
        });
    });
});
