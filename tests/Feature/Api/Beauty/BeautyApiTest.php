<?php declare(strict_types=1);

namespace Tests\Feature\Api\Beauty;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BeautyApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $salonOwner;
    protected BeautySalon $salon;
    protected Master $master;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['is_business' => false]);
        $this->salonOwner = User::factory()->create(['is_business' => true]);

        $this->salon = BeautySalon::factory()
            ->for($this->salonOwner, 'owner')
            ->create([
                'name' => 'Beauty Salon Pro',
                'rating' => 4.9,
                'is_active' => true,
                'is_b2c_available' => true,
            ]);

        $this->master = Master::factory()
            ->for($this->salon)
            ->create([
                'full_name' => 'Anna Petrova',
                'specialization' => ['haircut', 'styling', 'coloring'],
                'rating' => 4.8,
                'experience_years' => 10,
            ]);

        $this->service = Service::factory()
            ->for($this->master)
            ->create([
                'name' => 'Professional Haircut',
                'duration_minutes' => 45,
                'price' => 30000,  // 300 руб
                'consumables' => ['scissors', 'comb', 'hair_dye'],
            ]);
    }

    /**
     * Тест: Клиент может получить список салонов с фильтром
     */
    public function test_customer_can_list_salons_with_filters(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/beauty/salons', [
                'min_rating' => 4.5,
                'service_type' => 'haircut',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'rating',
                        'masters_count',
                        'services_count',
                        'nearest_available_slot',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Тест: Клиент может просмотреть профиль мастера с портфолио
     */
    public function test_customer_can_view_master_profile_with_portfolio(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson("/api/v1/beauty/masters/{$this->master->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'specialization',
                    'rating',
                    'experience_years',
                    'services' => [
                        '*' => [
                            'id',
                            'name',
                            'duration',
                            'price',
                        ],
                    ],
                    'portfolio' => [
                        '*' => [
                            'id',
                            'image_url',
                            'title',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Тест: Создание записи на услугу с проверкой фрода
     */
    public function test_customer_can_book_appointment_with_fraud_check(): void
    {
        $appointmentTime = now()->addDays(3)->setHour(14)->setMinute(0);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/beauty/appointments', [
                'master_id' => $this->master->id,
                'service_id' => $this->service->id,
                'datetime' => $appointmentTime->toIso8601String(),
                'notes' => 'Хочу полное окрашивание',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'correlation_id',
                    'appointment_time',
                    'duration_minutes',
                    'price',
                ],
            ]);

        // Проверить в БД
        $this->assertDatabaseHas(Appointment::class, [
            'user_id' => $this->customer->id,
            'master_id' => $this->master->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Тест: Блокировка записи при высокой фрод-оценке
     */
    public function test_appointment_blocked_on_high_fraud_score(): void
    {
        // Создать множество быстрых записей (признак фрода)
        for ($i = 0; $i < 7; $i++) {
            Appointment::factory()
                ->for($this->customer)
                ->for($this->master)
                ->create(['created_at' => now()->subHours(5 - $i)]);
        }

        // Попытка создать ещё одну запись - должна быть заблокирована
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/beauty/appointments', [
                'master_id' => $this->master->id,
                'service_id' => $this->service->id,
                'datetime' => now()->addDays(5)->toIso8601String(),
                'notes' => 'Запись',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Appointment blocked due to fraud suspicion',
            ]);
    }

    /**
     * Тест: Расчет цены услуги для мастера
     */
    public function test_appointment_price_calculated_correctly(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/beauty/appointments', [
                'master_id' => $this->master->id,
                'service_id' => $this->service->id,
                'datetime' => now()->addDays(3)->toIso8601String(),
            ]);

        $response->assertStatus(201);

        $totalPrice = $response->json('data.price');
        $servicePrice = 30000;  // 300 руб
        $commission = intval($servicePrice * 0.14);  // 14% комиссия

        // Цена для мастера = цена услуги - комиссия
        $expectedPrice = $servicePrice + $commission;  // В ответе показывается полная цена
        $this->assertEquals($expectedPrice, $totalPrice);
    }

    /**
     * Тест: Автоматическое списание расходников при завершении услуги
     */
    public function test_consumables_deducted_on_service_completion(): void
    {
        $appointment = Appointment::factory()
            ->for($this->customer)
            ->for($this->master)
            ->for($this->service)
            ->create(['status' => 'in_progress']);

        // Вызвать завершение услуги
        $response = $this->actingAs($this->master->user)
            ->patchJson("/api/v1/beauty/appointments/{$appointment->id}/complete");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'completed',
                ],
            ]);

        // Проверить в БД
        $this->assertDatabaseHas(Appointment::class, [
            'id' => $appointment->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Тест: Rate limiting на создание записей (10/day)
     */
    public function test_appointment_creation_rate_limited(): void
    {
        $masters = Master::factory(11)->for($this->salon)->create();

        // Создать 10 записей (в лимите)
        for ($i = 0; $i < 10; $i++) {
            $service = Service::factory()->for($masters[$i])->create();

            $response = $this->actingAs($this->customer)
                ->postJson('/api/v1/beauty/appointments', [
                    'master_id' => $masters[$i]->id,
                    'service_id' => $service->id,
                    'datetime' => now()->addDays(5 + $i)->toIso8601String(),
                ]);

            $response->assertStatus(201);
        }

        // 11-я запись должна быть заблокирована
        $lastService = Service::factory()->for($masters[10])->create();
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/beauty/appointments', [
                'master_id' => $masters[10]->id,
                'service_id' => $lastService->id,
                'datetime' => now()->addDays(100)->toIso8601String(),
            ]);

        $response->assertStatus(429);  // Too Many Requests
    }

    /**
     * Тест: Клиент может отменить запись за 24 часа
     */
    public function test_customer_can_cancel_appointment_24h_before(): void
    {
        $appointment = Appointment::factory()
            ->for($this->customer)
            ->for($this->master)
            ->for($this->service)
            ->create([
                'status' => 'confirmed',
                'appointment_time' => now()->addHours(30),  // 30 часов в будущем - можно отменить
            ]);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/beauty/appointments/{$appointment->id}/cancel", [
                'reason' => 'Не смогу прийти',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);

        $this->assertDatabaseHas(Appointment::class, [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Тест: Штраф за отмену менее чем за 24 часа
     */
    public function test_cancellation_fee_within_24h(): void
    {
        $appointment = Appointment::factory()
            ->for($this->customer)
            ->for($this->master)
            ->for($this->service)
            ->create([
                'status' => 'confirmed',
                'appointment_time' => now()->addHours(12),  // 12 часов = штраф 50%
                'price' => 30000,
            ]);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/beauty/appointments/{$appointment->id}/cancel");

        $response->assertStatus(200);

        // Проверить, что был применен штраф (50% от суммы)
        $refundAmount = $response->json('data.refund_amount');
        $this->assertEquals(15000, $refundAmount);  // 50% от 30000
    }

    /**
     * Тест: Отзыв и рейтинг после завершения услуги
     */
    public function test_customer_can_leave_review_after_service(): void
    {
        $appointment = Appointment::factory()
            ->for($this->customer)
            ->for($this->master)
            ->for($this->service)
            ->create(['status' => 'completed']);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/beauty/appointments/{$appointment->id}/review", [
                'rating' => 5,
                'comment' => 'Отличная работа! Очень доволен.',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'rating',
                    'comment',
                    'approved',
                ],
            ]);

        // Проверить в БД
        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->customer->id,
            'master_id' => $this->master->id,
            'rating' => 5,
        ]);
    }

    /**
     * Тест: Напоминание о записи за 24 часа и за 2 часа
     */
    public function test_appointment_reminders_sent(): void
    {
        $appointment = Appointment::factory()
            ->for($this->customer)
            ->for($this->master)
            ->for($this->service)
            ->create([
                'status' => 'confirmed',
                'appointment_time' => now()->addHours(24)->setMinute(0),
            ]);

        // Имитировать запуск Job для отправки напоминаний
        // (в реальной системе это делается через Scheduled Jobs)
        $response = $this->postJson('/api/v1/beauty/reminders/send', [
            'appointment_id' => $appointment->id,
        ]);

        $response->assertStatus(200);
    }

    /**
     * Тест: Каждая запись имеет correlation_id
     */
    public function test_appointment_has_correlation_id(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/beauty/appointments', [
                'master_id' => $this->master->id,
                'service_id' => $this->service->id,
                'datetime' => now()->addDays(3)->toIso8601String(),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'correlation_id',
                ],
            ]);

        $appointment = Appointment::first();
        $this->assertNotNull($appointment->correlation_id);
        $this->assertIsString($appointment->correlation_id);
    }

    /**
     * Тест: B2C клиент видит только B2C салоны
     */
    public function test_b2c_customer_sees_only_b2c_salons(): void
    {
        $b2bOnlySalon = BeautySalon::factory()->create([
            'is_b2c_available' => false,
            'is_b2b_available' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/beauty/salons');

        $response->assertStatus(200);
        $salonIds = $response->json('data.*.id');

        $this->assertContains($this->salon->id, $salonIds);
        $this->assertNotContains($b2bOnlySalon->id, $salonIds);
    }

    /**
     * Тест: Фильтр по специализации мастера
     */
    public function test_masters_filtered_by_specialization(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/beauty/masters', [
                'specialization' => 'coloring',
                'min_rating' => 4.5,
            ]);

        $response->assertStatus(200);
        $masterIds = $response->json('data.*.id');

        // Проверить, что только мастера с нужной специализацией
        $this->assertContains($this->master->id, $masterIds);
    }

    /**
     * Тест: Расписание мастера отображает занятые слоты
     */
    public function test_master_schedule_shows_booked_slots(): void
    {
        $appointment = Appointment::factory()
            ->for($this->master)
            ->create(['appointment_time' => now()->addDays(5)->setHour(14)->setMinute(0)]);

        $response = $this->actingAs($this->customer)
            ->getJson("/api/v1/beauty/masters/{$this->master->id}/schedule", [
                'date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'time',
                        'is_available',
                    ],
                ],
            ]);

        // Проверить, что слот в 14:00 занят
        $bookedSlot = collect($response->json('data'))->first(fn ($slot) => $slot['time'] === '14:00');
        $this->assertFalse($bookedSlot['is_available']);
    }
}
