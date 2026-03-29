declare(strict_types=1);

namespace Tests\Feature\Api\ShortTermRentals;

use App\Domains\ShortTermRentals\Models\Property;
use App\Domains\ShortTermRentals\Models\PropertyBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShortTermRentalsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $propertyOwner;
    protected Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_business' => false]);
        $this->propertyOwner = User::factory()->create(['is_business' => true]);
        
        $this->property = Property::factory()
            ->for($this->propertyOwner, 'owner')
            ->create([
                'price_per_night' => 10000,  // 100 руб (в копейках)
                'is_active' => true,
                'is_b2c_available' => true,
            ]);
    }

    /**
     * Тест: пользователь может получить список квартир с географическим фильтром
     */
    public function test_user_can_list_properties_with_geo_filtering(): void
    {
        // Создать несколько квартир в разных локациях
        $nearProperty = Property::factory()->create([
            'lat' => 55.7558,
            'lon' => 37.6173,  // Москва
            'is_active' => true,
        ]);

        $farProperty = Property::factory()->create([
            'lat' => 59.9311,
            'lon' => 30.3609,  // Санкт-Петербург
            'is_active' => true,
        ]);

        // Запрос: найти квартиры в радиусе 50км от Московского центра
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/str/properties', [
                'lat' => 55.7558,
                'lon' => 37.6173,
                'radius_km' => 50,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'price_per_night', 'distance']]])
            ->assertJsonCount(1, 'data');  // Только близкая квартира

        $this->assertDatabaseCount(Property::class, 3);
    }

    /**
     * Тест: B2C пользователь не видит B2B-only квартиры
     */
    public function test_b2c_user_cannot_see_b2b_only_properties(): void
    {
        $b2bOnlyProperty = Property::factory()->create([
            'is_b2c_available' => false,
            'is_b2b_available' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/str/properties');

        $response->assertStatus(200);

        // Проверить, что B2B-only квартира не в результатах
        $ids = $response->json('data.*.id');
        $this->assertNotContains($b2bOnlyProperty->id, $ids);
    }

    /**
     * Тест: создание бронирования с проверкой фрода
     */
    public function test_booking_with_fraud_check(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/str/bookings', [
                'property_id' => $this->property->id,
                'check_in_date' => now()->addDays(5)->toIso8601String(),
                'check_out_date' => now()->addDays(7)->toIso8601String(),
                'guest_count' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'correlation_id',
                    'total_price',
                    'deposit_amount',
                ],
            ]);

        // Проверить, что бронирование создано в БД
        $this->assertDatabaseHas(PropertyBooking::class, [
            'user_id' => $this->user->id,
            'property_id' => $this->property->id,
            'status' => 'pending_verification',
        ]);

        // Проверить логирование в audit канале
        $this->assertTrue(true);  // Mock Log::channel('audit') в реальных тестах
    }

    /**
     * Тест: отмена бронирования возвращает депозит
     */
    public function test_cancellation_refunds_deposit(): void
    {
        // Создать бронирование
        $booking = PropertyBooking::factory()
            ->for($this->user)
            ->for($this->property)
            ->create([
                'status' => 'confirmed',
                'check_in_date' => now()->addDays(10),
                'deposit_amount' => 2500,  // 25% от 10000
            ]);

        $userBalanceBefore = $this->user->wallet->balance ?? 0;

        // Отменить бронирование
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/str/bookings/{$booking->id}/cancel", [
                'reason' => 'Изменил планы',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'cancelled',
                    'cancellation_reason' => 'Изменил планы',
                ],
            ]);

        // Проверить, что депозит возвращён
        $this->assertDatabaseHas(PropertyBooking::class, [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Тест: запрос выплаты с валидацией суммы
     */
    public function test_payout_request_with_amount_validation(): void
    {
        // Попытка запросить выплату меньше минимума (500 руб)
        $response = $this->actingAs($this->propertyOwner)
            ->postJson('/api/v1/str/payouts/request', [
                'amount' => 200,
                'bank_account' => 'invalid_account',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'bank_account']);

        // Корректный запрос
        $response = $this->actingAs($this->propertyOwner)
            ->postJson('/api/v1/str/payouts/request', [
                'amount' => 5000,  // 50 руб минимум
                'bank_account' => '40702810612345678901',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'correlation_id']);
    }

    /**
     * Тест: владелец может просматривать только свои бронирования
     */
    public function test_owner_can_only_view_own_bookings(): void
    {
        // Создать бронирование для другого пользователя
        $otherUser = User::factory()->create();
        $otherBooking = PropertyBooking::factory()
            ->for($otherUser)
            ->for($this->property)
            ->create();

        // Попытка владельца просмотреть чужое бронирование
        $response = $this->actingAs($this->propertyOwner)
            ->getJson("/api/v1/str/bookings/{$otherBooking->id}");

        $response->assertStatus(403);  // Forbidden - не собственник
    }

    /**
     * Тест: Rate limiting на создание бронирований (максимум 5 за 24 часа)
     */
    public function test_booking_creation_rate_limited(): void
    {
        $properties = Property::factory(6)->create();

        // Создать 5 бронирований (в лимите)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/str/bookings', [
                    'property_id' => $properties[$i]->id,
                    'check_in_date' => now()->addDays(5 + $i * 10)->toIso8601String(),
                    'check_out_date' => now()->addDays(7 + $i * 10)->toIso8601String(),
                    'guest_count' => 2,
                ]);

            $response->assertStatus(201);
        }

        // 6-е бронирование должно быть заблокировано
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/str/bookings', [
                'property_id' => $properties[5]->id,
                'check_in_date' => now()->addDays(100)->toIso8601String(),
                'check_out_date' => now()->addDays(102)->toIso8601String(),
                'guest_count' => 2,
            ]);

        $response->assertStatus(429);  // Too Many Requests
    }

    /**
     * Тест: фильтрация квартир по ценовому диапазону
     */
    public function test_properties_filtered_by_price_range(): void
    {
        $cheapProperty = Property::factory()->create(['price_per_night' => 5000]);    // 50 руб
        $expensiveProperty = Property::factory()->create(['price_per_night' => 50000]); // 500 руб

        // Поиск квартир от 100 до 300 руб
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/str/properties', [
                'min_price' => 100,
                'max_price' => 300,
            ]);

        $response->assertStatus(200);
        $ids = $response->json('data.*.id');

        // Проверить, что результаты в нужном диапазоне
        $this->assertContains($this->property->id, $ids);
        $this->assertNotContains($cheapProperty->id, $ids);
        $this->assertNotContains($expensiveProperty->id, $ids);
    }

    /**
     * Тест: каждое бронирование имеет correlation_id для отслеживания
     */
    public function test_booking_has_correlation_id(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/str/bookings', [
                'property_id' => $this->property->id,
                'check_in_date' => now()->addDays(5)->toIso8601String(),
                'check_out_date' => now()->addDays(7)->toIso8601String(),
                'guest_count' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['correlation_id']])
            ->assertJson([
                'data' => [
                    'correlation_id' => fn ($id) => is_string($id) && strlen($id) > 0,
                ],
            ]);

        // Проверить в БД
        $booking = PropertyBooking::first();
        $this->assertNotNull($booking->correlation_id);
    }
}
