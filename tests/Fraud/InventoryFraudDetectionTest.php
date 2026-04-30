<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Inventory\Domain\Entities\Stock;
use Modules\Inventory\Domain\Entities\Warehouse;
use Modules\Inventory\Domain\Enums\StockStatus;
use Illuminate\Support\Facades\Cache;

final class InventoryFraudDetectionTest extends BaseFraudTest
{
    public function test_stock_manipulation(): void
    {
        $warehouse = Warehouse::factory()->create();
        $productSku = 'SKU_001';

        $stock = Stock::create([
            'warehouse_id' => $warehouse->id,
            'sku' => $productSku,
            'quantity' => 100,
            'status' => StockStatus::Available,
        ]);

        // Artificial stock inflation
        $stock->update(['quantity' => 10000]);

        $this->fraudControl->checkInventoryFraud($stock);

        $this->assertFraudAlertCreated(
            'stock',
            $stock->id,
            FraudType::InventoryManipulation,
            FraudSeverity::Critical
        );
    }

    public function test_theft_detection(): void
    {
        $warehouse = Warehouse::factory()->create();

        // Simulate unexplained stock loss
        for ($i = 0; $i < 50; $i++) {
            $stock = Stock::create([
                'warehouse_id' => $warehouse->id,
                'sku' => "SKU_{$i}",
                'quantity' => 100,
                'status' => StockStatus::Available,
            ]);

            // Sudden disappearance
            $stock->update([
                'quantity' => 0,
                'status' => StockStatus::Lost,
                'loss_reason' => null, // No explanation
            ]);
        }

        $this->fraudControl->checkTheftPattern([
            'warehouse_id' => $warehouse->id,
            'loss_count' => 50,
            'time_window_days' => 7,
            'total_loss_value' => 500000,
        ]);

        $this->assertFraudAlertCreated(
            'warehouse',
            $warehouse->id,
            FraudType::Theft,
            FraudSeverity::Critical
        );
    }

    public function test_fake_stock_transfer(): void
    {
        $warehouse1 = Warehouse::factory()->create();
        $warehouse2 = Warehouse::factory()->create();

        // Simulate fake transfers between warehouses
        for ($i = 0; $i < 20; $i++) {
            $this->fraudControl->checkTransferFraud([
                'from_warehouse_id' => $warehouse1->id,
                'to_warehouse_id' => $warehouse2->id,
                'sku' => "SKU_{$i}",
                'quantity' => 1000,
                'verified_at_destination' => false,
                'transfer_count' => 20,
            ]);
        }

        $this->assertFraudAlertCreated(
            'warehouse',
            $warehouse1->id,
            FraudType::InventoryManipulation,
            FraudSeverity::High
        );
    }

    public function test_supplier_fraud(): void
    {
        $supplierId = 1;

        // Simulate supplier sending inferior quality goods
        for ($i = 0; $i < 15; $i++) {
            $this->fraudControl->checkSupplierFraud([
                'supplier_id' => $supplierId,
                'quality_check_passed' => false,
                'quantity_mismatch' => true,
                'price_inflation' => 1.5,
            ]);
        }

        $this->assertFraudAlertCreated(
            'supplier',
            $supplierId,
            FraudType::SupplierFraud,
            FraudSeverity::High
        );
    }

    public function test_cycle_count_manipulation(): void
    {
        $warehouse = Warehouse::factory()->create();

        // Simulate fake cycle counts to hide theft
        $this->fraudControl->checkCycleCountFraud([
            'warehouse_id' => $warehouse->id,
            'expected_quantity' => 10000,
            'reported_quantity' => 10000,
            'actual_quantity' => 5000,
            'variance_percentage' => 50,
            'count_performed_by' => 'internal_staff',
        ]);

        $this->assertFraudAlertCreated(
            'warehouse',
            $warehouse->id,
            FraudType::DataTampering,
            FraudSeverity::Critical
        );
    }
}
