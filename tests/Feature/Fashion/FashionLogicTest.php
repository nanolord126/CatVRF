<?php declare(strict_types=1);

namespace Tests\Feature\Fashion;

use App\Domains\Fashion\Jobs\ReleaseStockReservationJob;
use App\Domains\Fashion\Models\FashionProduct;
use App\Domains\Fashion\Models\FashionStore;
use App\Domains\Fashion\Services\FashionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * FashionLogicTest
 * 
 * Тестирование критической бизнес-логики вертикали Fashion 2026:
 * - 20-минутный резерв.
 * - Защита цены (не обновляем old_price, если цена выросла).
 * - B2B/B2C изоляция.
 */
class FashionLogicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /**
     * ТЕСТ 1: 20-минутный резерв
     */
    public function test_can_reserve_stock_and_dispatch_release_job(): void
    {
        $store = FashionStore::factory()->create(['type' => 'b2c']);
        $product = FashionProduct::factory()->create([
            'store_id' => $store->id,
            'quantity' => 10,
            'reserve_quantity' => 0
        ]);

        $service = app(FashionService::class, ['correlation_id' => 'test-reserve']);
        
        // Резервируем 3 единицы
        $success = $service->reserveItem($product->id, 3, 'test-correlation');

        $this->assertTrue($success);
        
        // Проверяем БД
        $this->assertDatabaseHas('fashion_products', [
            'id' => $product->id,
            'reserve_quantity' => 3
        ]);

        // Проверяем, что джоба на сброс встала в очередь
        Queue::assertPushed(ReleaseStockReservationJob::class);
    }

    /**
     * ТЕСТ 2: Защита цены (Канон 2026)
     * "Цена подешевела — оставляем старую (old_price). Если выросла — не обновляем old_price."
     */
    public function test_price_protection_logic(): void
    {
        $product = FashionProduct::factory()->create([
            'price_b2c' => 5000, // 50 руб
            'old_price' => null
        ]);

        // 1. Подешевела до 4000
        $product->update(['price_b2c' => 4000]);
        $product->refresh();
        
        $this->assertEquals(4000, $product->price_b2c);
        $this->assertEquals(5000, $product->old_price); // Старая цена зафиксирована

        // 2. Снова подешевела до 3000
        $product->update(['price_b2c' => 3000]);
        $product->refresh();
        
        $this->assertEquals(3000, $product->price_b2c);
        $this->assertEquals(4000, $product->old_price); // Старая цена обновилась до последней (4000)

        // 3. Пакетная цена: выросла до 4500 (Не должна менять old_price на 3000)
        $product->update(['price_b2c' => 4500]);
        $product->refresh();
        
        $this->assertEquals(4500, $product->price_b2c);
        $this->assertEquals(4000, $product->old_price); // Осталась та, от которой падали
    }

    /**
     * ТЕСТ 3: Доступность товара с учетом резерва
     */
    public function test_available_stock_calculation(): void
    {
        $product = FashionProduct::factory()->create([
            'quantity' => 10,
            'reserve_quantity' => 8
        ]);

        // 10 - 8 = 2
        $this->assertEquals(2, $product->available_stock);

        $product->increment('reserve_quantity', 1);
        $this->assertEquals(1, $product->available_stock);
        
        $product->increment('reserve_quantity', 1);
        $this->assertEquals(0, $product->available_stock);
    }

    /**
     * ТЕСТ 4: B2B/B2C Изоляция цен в API
     */
    public function test_b2b_vs_b2c_pricing_access(): void
    {
        $product = FashionProduct::factory()->create([
            'price_b2c' => 10000,
            'price_b2b' => 7000
        ]);

        // Эмитируем запрос розничного каталога
        $response = $this->getJson('/api/v1/fashion/products');
        
        $response->assertStatus(200);
        $response->assertJsonFragment(['price_b2c' => 10000]);
        $response->assertJsonMissing(['price_b2b' => 7000]);

        // Опт
        $responseB2B = $this->getJson('/api/v1/fashion/b2b/catalog?inn=1234567890');
        $responseB2B->assertStatus(200);
        $responseB2B->assertJsonFragment(['price_b2b' => 7000]);
    }
}
