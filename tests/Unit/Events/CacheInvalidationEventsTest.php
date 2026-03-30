<?php declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\AIConstructorDesignSaved;
use App\Events\MasterAvailabilityChanged;
use App\Events\ProductInventoryChanged;
use App\Events\UserTasteProfileChanged;
use App\Events\VerticalStatsRecalculated;
use PHPUnit\Framework\TestCase;

final class CacheInvalidationEventsTest extends TestCase
{
    public function test_user_taste_profile_changed_event_structure(): void
    {
        $userId = 123;
        $correlationId = 'corr-id-123';

        $event = new UserTasteProfileChanged(
            userId: $userId,
            correlationId: $correlationId,
        );

        $this->assertEquals($userId, $event->userId);
        $this->assertEquals($correlationId, $event->correlationId);
    }

    public function test_product_inventory_changed_event_structure(): void
    {
        $productId = 456;
        $vertical = 'beauty';

        $event = new ProductInventoryChanged(
            productId: $productId,
            vertical: $vertical,
            oldQuantity: 100,
            newQuantity: 50,
            correlationId: 'corr-id-456',
        );

        $this->assertEquals($productId, $event->productId);
        $this->assertEquals($vertical, $event->vertical);
        $this->assertEquals(100, $event->oldQuantity);
        $this->assertEquals(50, $event->newQuantity);
    }

    public function test_master_availability_changed_event_structure(): void
    {
        $masterId = 789;
        $vertical = 'beauty';

        $event = new MasterAvailabilityChanged(
            masterId: $masterId,
            vertical: $vertical,
            correlationId: 'corr-id-789',
        );

        $this->assertEquals($masterId, $event->masterId);
        $this->assertEquals($vertical, $event->vertical);
    }

    public function test_ai_constructor_design_saved_event_structure(): void
    {
        $userId = 111;
        $vertical = 'furniture';

        $event = new AIConstructorDesignSaved(
            userId: $userId,
            vertical: $vertical,
            correlationId: 'corr-id-111',
        );

        $this->assertEquals($userId, $event->userId);
        $this->assertEquals($vertical, $event->vertical);
    }

    public function test_vertical_stats_recalculated_event_structure(): void
    {
        $vertical = 'food';
        $correlationId = 'corr-id-222';

        $event = new VerticalStatsRecalculated(
            vertical: $vertical,
            correlationId: $correlationId,
        );

        $this->assertEquals($vertical, $event->vertical);
        $this->assertEquals($correlationId, $event->correlationId);
    }
}
