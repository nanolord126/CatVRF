declare(strict_types=1);

namespace Tests\Feature\Api\Hotels;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\Room;
use App\Domains\Hotels\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HotelsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $guestUser;
    protected User $hotelOwner;
    protected Hotel $hotel;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guestUser = User::factory()->create(['is_business' => false]);
        $this->hotelOwner = User::factory()->create(['is_business' => true]);

        $this->hotel = Hotel::factory()
            ->for($this->hotelOwner, 'owner')
            ->create([
                'name' => 'Luxury Hotel',
                'rating' => 4.8,
                'is_active' => true,
            ]);

        $this->room = Room::factory()
            ->for($this->hotel)
            ->create([
                'room_number' => '101',
                'price_per_night' => 20000,  // 200 руб
                'capacity' => 2,
                'is_available' => true,
            ]);
    }

    /**
     * Тест: Гость может получить список доступных отелей
     */
    public function test_guest_can_list_available_hotels(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->getJson('/api/v1/hotels');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'address',
                        'rating',
                        'available_rooms_count',
                        'price_from',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Тест: Гость может просмотреть детали отеля с доступными номерами
     */
    public function test_guest_can_view_hotel_details_with_available_rooms(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->getJson("/api/v1/hotels/{$this->hotel->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'address',
                    'rating',
                    'description',
                    'rooms' => [
                        '*' => [
                            'id',
                            'room_number',
                            'price_per_night',
                            'capacity',
                            'is_available',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $this->hotel->id,
                    'name' => 'Luxury Hotel',
                ],
            ]);
    }

    /**
     * Тест: Гость может создать бронирование с проверкой фрода
     */
    public function test_guest_can_create_booking_with_fraud_check(): void
    {
        $checkInDate = now()->addDays(5)->toDateString();
        $checkOutDate = now()->addDays(7)->toDateString();

        $response = $this->actingAs($this->guestUser)
            ->postJson('/api/v1/hotels/bookings', [
                'room_id' => $this->room->id,
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'guest_count' => 2,
                'special_requests' => 'Бесплатный завтрак',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'correlation_id',
                    'total_price',
                    'check_in_date',
                    'check_out_date',
                ],
            ]);

        // Проверить в БД
        $this->assertDatabaseHas(Booking::class, [
            'user_id' => $this->guestUser->id,
            'room_id' => $this->room->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Тест: Блокировка бронирования при высокой фрод-оценке
     */
    public function test_booking_blocked_on_high_fraud_score(): void
    {
        // Создать множество быстрых бронирований (признак фрода)
        for ($i = 0; $i < 5; $i++) {
            Booking::factory()
                ->for($this->guestUser)
                ->for($this->room)
                ->create(['created_at' => now()->subMinutes(5 - $i)]);
        }

        // Попытка создать ещё одно бронирование - должна быть заблокирована
        $response = $this->actingAs($this->guestUser)
            ->postJson('/api/v1/hotels/bookings', [
                'room_id' => $this->room->id,
                'check_in_date' => now()->addDays(20)->toDateString(),
                'check_out_date' => now()->addDays(22)->toDateString(),
                'guest_count' => 2,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Booking blocked due to fraud suspicion',
            ]);
    }

    /**
     * Тест: Гость может отменить бронирование за 48 часов до чекина
     */
    public function test_guest_can_cancel_booking_within_48_hours(): void
    {
        $booking = Booking::factory()
            ->for($this->guestUser)
            ->for($this->room)
            ->create([
                'status' => 'confirmed',
                'check_in_date' => now()->addDays(3),  // 72 часа = в пределах refund period
                'total_price' => 40000,  // 400 руб
            ]);

        $response = $this->actingAs($this->guestUser)
            ->postJson("/api/v1/hotels/bookings/{$booking->id}/cancel", [
                'reason' => 'Отмена планов',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'cancelled',
                    'refund_amount' => 40000,  // 100% refund
                ],
            ]);

        $this->assertDatabaseHas(Booking::class, [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Тест: Штраф за отмену менее чем за 48 часов
     */
    public function test_cancellation_penalty_within_48_hours(): void
    {
        $booking = Booking::factory()
            ->for($this->guestUser)
            ->for($this->room)
            ->create([
                'status' => 'confirmed',
                'check_in_date' => now()->addHours(20),  // 20 часов = штраф 50%
                'total_price' => 40000,
            ]);

        $response = $this->actingAs($this->guestUser)
            ->postJson("/api/v1/hotels/bookings/{$booking->id}/cancel");

        $response->assertStatus(200);

        // Проверить, что был применен штраф
        $refundAmount = $response->json('data.refund_amount');
        $this->assertEquals(20000, $refundAmount);  // 50% от 40000
    }

    /**
     * Тест: Владелец отеля может просмотреть все бронирования
     */
    public function test_hotel_owner_can_view_all_bookings(): void
    {
        // Создать несколько бронирований
        Booking::factory(3)
            ->for($this->room)
            ->create();

        $response = $this->actingAs($this->hotelOwner)
            ->getJson("/api/v1/hotels/{$this->hotel->id}/bookings");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Тест: Владелец может подтвердить бронирование
     */
    public function test_hotel_owner_can_confirm_booking(): void
    {
        $booking = Booking::factory()
            ->for($this->room)
            ->create(['status' => 'pending']);

        $response = $this->actingAs($this->hotelOwner)
            ->patchJson("/api/v1/hotels/bookings/{$booking->id}/confirm");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'confirmed',
                ],
            ]);

        $this->assertDatabaseHas(Booking::class, [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    /**
     * Тест: Выплата владельцу через 4 дня после выселения
     */
    public function test_payout_scheduled_4_days_after_checkout(): void
    {
        $booking = Booking::factory()
            ->for($this->room)
            ->create([
                'status' => 'checked_out',
                'check_out_date' => now()->subDays(4),  // Выселение 4 дня назад
                'total_price' => 100000,  // 1000 руб
                'platform_fee_percent' => 14,
            ]);

        // Вызвать job выплаты
        $response = $this->actingAs($this->hotelOwner)
            ->postJson('/api/v1/hotels/payouts/schedule');

        $response->assertStatus(200);

        // Проверить, что бронирование помечено как "payout_processed"
        $this->assertDatabaseHas(Booking::class, [
            'id' => $booking->id,
            'payout_status' => 'processed',
        ]);
    }

    /**
     * Тест: Невозможно забронировать номер на занятую дату
     */
    public function test_cannot_book_room_on_occupied_dates(): void
    {
        // Существующее бронирование
        Booking::factory()
            ->for($this->room)
            ->create([
                'status' => 'confirmed',
                'check_in_date' => now()->addDays(10),
                'check_out_date' => now()->addDays(12),
            ]);

        // Попытка забронировать на пересекающиеся даты
        $response = $this->actingAs($this->guestUser)
            ->postJson('/api/v1/hotels/bookings', [
                'room_id' => $this->room->id,
                'check_in_date' => now()->addDays(11)->toDateString(),  // Пересекается
                'check_out_date' => now()->addDays(13)->toDateString(),
                'guest_count' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('check_in_date');
    }

    /**
     * Тест: Rate limiting на количество бронирований
     */
    public function test_booking_creation_rate_limited(): void
    {
        $rooms = Room::factory(11)->for($this->hotel)->create();

        // Попытка создать 11 бронирований (лимит 10/24h)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($this->guestUser)
                ->postJson('/api/v1/hotels/bookings', [
                    'room_id' => $rooms[$i]->id,
                    'check_in_date' => now()->addDays(5 + $i * 10)->toDateString(),
                    'check_out_date' => now()->addDays(6 + $i * 10)->toDateString(),
                    'guest_count' => 2,
                ]);

            $response->assertStatus(201);
        }

        // 11-е бронирование должно быть заблокировано
        $response = $this->actingAs($this->guestUser)
            ->postJson('/api/v1/hotels/bookings', [
                'room_id' => $rooms[10]->id,
                'check_in_date' => now()->addDays(105)->toDateString(),
                'check_out_date' => now()->addDays(106)->toDateString(),
                'guest_count' => 2,
            ]);

        $response->assertStatus(429);  // Too Many Requests
    }

    /**
     * Тест: Каждое бронирование имеет correlation_id
     */
    public function test_booking_has_correlation_id(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->postJson('/api/v1/hotels/bookings', [
                'room_id' => $this->room->id,
                'check_in_date' => now()->addDays(5)->toDateString(),
                'check_out_date' => now()->addDays(7)->toDateString(),
                'guest_count' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'correlation_id',
                ],
            ]);

        // Проверить, что correlation_id в БД
        $booking = Booking::first();
        $this->assertNotNull($booking->correlation_id);
        $this->assertIsString($booking->correlation_id);
    }

    /**
     * Тест: B2C пользователь видит только B2C отели
     */
    public function test_b2c_user_sees_only_b2c_hotels(): void
    {
        $b2bOnlyHotel = Hotel::factory()->create([
            'is_b2c_available' => false,
            'is_b2b_available' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->guestUser)
            ->getJson('/api/v1/hotels');

        $response->assertStatus(200);
        $hotelIds = $response->json('data.*.id');

        $this->assertContains($this->hotel->id, $hotelIds);
        $this->assertNotContains($b2bOnlyHotel->id, $hotelIds);
    }
}
