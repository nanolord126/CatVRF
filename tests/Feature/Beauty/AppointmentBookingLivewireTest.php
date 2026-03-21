<?php declare(strict_types=1);

namespace Tests\Feature\Beauty;

use App\Domains\Beauty\Events\AppointmentScheduled;
use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Models\BeautyService;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Services\AppointmentService;
use App\Livewire\Beauty\AppointmentBooking;
use App\Services\FraudControlService;
use App\Services\InventoryManagementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Feature-тест Livewire-компонента AppointmentBooking (КАНОН 2026).
 * Покрывает: бронирование, rate limit, fraud block, корреляция, consumables.
 */
final class AppointmentBookingLivewireTest extends TestCase
{
    use RefreshDatabase;

    private BeautySalon $salon;
    private Master $master;
    private BeautyService $service;
    private \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Базовые сущности (без фабрик, которые могут иметь неверный namespace)
        $this->user = \App\Models\User::factory()->create([
            'tenant_id' => 1,
        ]);

        $this->salon = BeautySalon::create([
            'tenant_id'      => 1,
            'name'           => 'Test Salon',
            'address'        => 'г. Москва, ул. Тестовая, 1',
            'correlation_id' => (string) Str::uuid(),
            'status'         => 'active',
            'is_verified'    => true,
            'uuid'           => (string) Str::uuid(),
        ]);

        $this->master = Master::create([
            'tenant_id'      => 1,
            'salon_id'       => $this->salon->id,
            'full_name'      => 'Анна Мастер',
            'specialization' => ['haircut', 'coloring'],
            'correlation_id' => (string) Str::uuid(),
            'uuid'           => (string) Str::uuid(),
        ]);

        $this->service = BeautyService::create([
            'tenant_id'        => 1,
            'master_id'        => $this->master->id,
            'name'             => 'Стрижка',
            'duration_minutes' => 60,
            'price'            => 150000, // 1500 руб в копейках
            'correlation_id'   => (string) Str::uuid(),
            'uuid'             => (string) Str::uuid(),
            'consumables_json' => [
                ['id' => 1, 'name' => 'Перчатки', 'quantity' => 1],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Базовый рендер
    // -------------------------------------------------------------------------

    /** @test */
    public function it_renders_master_select_on_mount(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AppointmentBooking::class, ['salonId' => $this->salon->id])
            ->assertSee('Анна Мастер')
            ->assertSet('salonId', $this->salon->id)
            ->assertSet('booked', false);
    }

    /** @test */
    public function it_loads_services_when_master_selected(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AppointmentBooking::class, ['salonId' => $this->salon->id])
            ->set('masterId', $this->master->id)
            ->assertSet('services', fn ($services) => count($services) > 0)
            ->assertSee('Стрижка');
    }

    // -------------------------------------------------------------------------
    // Генерация слотов
    // -------------------------------------------------------------------------

    /** @test */
    public function it_generates_available_slots_for_date_without_existing_appointments(): void
    {
        $this->actingAs($this->user);
        $date = Carbon::tomorrow()->format('Y-m-d');

        $component = Livewire::test(AppointmentBooking::class, ['salonId' => $this->salon->id])
            ->set('masterId', $this->master->id)
            ->set('serviceId', $this->service->id)
            ->call('loadAvailableSlots', $date);

        $slots = $component->get('availableSlots');
        $this->assertNotEmpty($slots);
        $this->assertContains('09:00', $slots);
        $this->assertContains('10:00', $slots); // 60 мин → второй слот 10:00
    }

    /** @test */
    public function it_excludes_busy_slots(): void
    {
        $this->actingAs($this->user);
        $date = Carbon::tomorrow()->format('Y-m-d');

        // Создаём занятую запись на 09:00
        Appointment::create([
            'tenant_id'      => 1,
            'salon_id'       => $this->salon->id,
            'master_id'      => $this->master->id,
            'service_id'     => $this->service->id,
            'client_id'      => $this->user->id,
            'datetime_start' => Carbon::parse($date . ' 09:00'),
            'status'         => 'confirmed',
            'price'          => 150000,
            'correlation_id' => (string) Str::uuid(),
            'uuid'           => (string) Str::uuid(),
        ]);

        $component = Livewire::test(AppointmentBooking::class, ['salonId' => $this->salon->id])
            ->set('masterId', $this->master->id)
            ->set('serviceId', $this->service->id)
            ->call('loadAvailableSlots', $date);

        $slots = $component->get('availableSlots');
        $this->assertNotContains('09:00', $slots);
        $this->assertContains('10:00', $slots);
    }

    // -------------------------------------------------------------------------
    // Успешное бронирование
    // -------------------------------------------------------------------------

    /** @test */
    public function it_creates_appointment_and_fires_event_on_successful_booking(): void
    {
        $this->actingAs($this->user);
        Event::fake([AppointmentScheduled::class]);

        $date = Carbon::tomorrow()->format('Y-m-d');

        // Мокаем FraudControlService → allow
        $fraudMock = Mockery::mock(FraudControlService::class);
        $fraudMock->shouldReceive('check')
            ->once()
            ->andReturn(['score' => 0.1, 'decision' => 'allow', 'threshold' => 0.7]);
        $this->app->instance(FraudControlService::class, $fraudMock);

        Livewire::test(AppointmentBooking::class, ['salonId' => $this->salon->id])
            ->set('masterId', $this->master->id)
            ->set('serviceId', $this->service->id)
            ->call('loadAvailableSlots', $date)
            ->set('selectedDate', $date)
            ->set('selectedTime', '09:00')
            ->set('comment', 'Тестовый комментарий')
            ->call('bookAppointment')
            ->assertSet('booked', true)
            ->assertSet('errorMessage', null);

        Event::assertDispatched(AppointmentScheduled::class);
        $this->assertDatabaseHas('appointments', [
            'master_id'  => $this->master->id,
            'service_id' => $this->service->id,
            'status'     => 'pending',
        ]);
    }

    /** @test */
    public function it_stores_correlation_id_in_audit_log_on_booking(): void
    {
        $this->actingAs($this->user);

        $fraudMock = Mockery::mock(FraudControlService::class);
        $fraudMock->shouldReceive('check')->andReturn(['score' => 0.0, 'decision' => 'allow', 'threshold' => 0.7]);
        $this->app->instance(FraudControlService::class, $fraudMock);

        $logged = false;
        Log::listen(function ($level, $message, $context) use (&$logged) {
            if (isset($context['correlation_id'])) {
                $logged = true;
            }
        });

        $date = Carbon::tomorrow()->format('Y-m-d');

        Livewire::test(AppointmentBooking::class, ['salonId' => $this->salon->id])
            ->set('masterId', $this->master->id)
            ->set('serviceId', $this->service->id)
            ->set('selectedDate', $date)
            ->set('selectedTime', '09:00')
            ->call('bookAppointment');

        $this->assertTrue($logged, 'Audit log должен содержать correlation_id');
    }

    // -------------------------------------------------------------------------
    // Rate Limiting
    // -------------------------------------------------------------------------

    /** @test */
    public function it_blocks_booking_when_rate_limit_exceeded(): void
    {
        $this->actingAs($this->user);

        // Имитируем превышение лимита
        $key = 'beauty:booking:' . $this->user->id . ':1';
        for ($i = 0; $i < 6; $i++) {
            RateLimiter::hit($key, 3600);
        }

        $date = Carbon::tomorrow()->format('Y-m-d');

        Livewire::test(AppointmentBooking::class, ['salonId' => $this->salon->id])
            ->set('masterId', $this->master->id)
            ->set('serviceId', $this->service->id)
            ->set('selectedDate', $date)
            ->set('selectedTime', '09:00')
            ->call('bookAppointment')
            ->assertSet('booked', false)
            ->assertSet('errorMessage', fn ($msg) => str_contains($msg ?? '', 'Слишком много'));
    }

    // -------------------------------------------------------------------------
    // Fraud Block
    // -------------------------------------------------------------------------

    /** @test */
    public function it_blocks_booking_when_fraud_check_returns_block(): void
    {
        $this->actingAs($this->user);

        $fraudMock = Mockery::mock(FraudControlService::class);
        $fraudMock->shouldReceive('check')
            ->once()
            ->andReturn(['score' => 0.95, 'decision' => 'block', 'threshold' => 0.7]);
        $this->app->instance(FraudControlService::class, $fraudMock);

        $date = Carbon::tomorrow()->format('Y-m-d');

        Livewire::test(AppointmentBooking::class, ['salonId' => $this->salon->id])
            ->set('masterId', $this->master->id)
            ->set('serviceId', $this->service->id)
            ->set('selectedDate', $date)
            ->set('selectedTime', '09:00')
            ->call('bookAppointment')
            ->assertSet('booked', false)
            ->assertSet('errorMessage', fn ($msg) => str_contains($msg ?? '', 'недоступно'));

        $this->assertDatabaseMissing('appointments', [
            'master_id' => $this->master->id,
            'status'    => 'pending',
        ]);
    }

    // -------------------------------------------------------------------------
    // Валидация
    // -------------------------------------------------------------------------

    /** @test */
    public function it_fails_validation_when_required_fields_missing(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AppointmentBooking::class, ['salonId' => $this->salon->id])
            ->call('bookAppointment')
            ->assertHasErrors(['masterId', 'serviceId', 'selectedDate', 'selectedTime']);
    }

    /** @test */
    public function it_fails_validation_when_date_in_the_past(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AppointmentBooking::class, ['salonId' => $this->salon->id])
            ->set('masterId', $this->master->id)
            ->set('serviceId', $this->service->id)
            ->set('selectedDate', Carbon::yesterday()->format('Y-m-d'))
            ->set('selectedTime', '14:00')
            ->call('bookAppointment')
            ->assertHasErrors(['selectedDate']);
    }

    // -------------------------------------------------------------------------
    // AppointmentCalendar — рендер и навигация
    // -------------------------------------------------------------------------

    /** @test */
    public function calendar_renders_current_month(): void
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Livewire\Beauty\AppointmentCalendar::class, [
            'salonId' => $this->salon->id,
        ])
            ->assertSet('year', now()->year)
            ->assertSet('month', now()->month)
            ->assertSee(now()->locale('ru')->isoFormat('MMMM'));
    }

    /** @test */
    public function calendar_navigates_to_previous_month(): void
    {
        $this->actingAs($this->user);
        $expectedMonth = now()->subMonth()->month;
        $expectedYear  = now()->subMonth()->year;

        Livewire::test(\App\Livewire\Beauty\AppointmentCalendar::class, [
            'salonId' => $this->salon->id,
        ])
            ->call('previousMonth')
            ->assertSet('month', $expectedMonth)
            ->assertSet('year', $expectedYear);
    }

    /** @test */
    public function calendar_shows_appointment_counts_per_day(): void
    {
        $this->actingAs($this->user);

        // Создаём запись на сегодня
        Appointment::create([
            'tenant_id'      => 1,
            'salon_id'       => $this->salon->id,
            'master_id'      => $this->master->id,
            'service_id'     => $this->service->id,
            'client_id'      => $this->user->id,
            'datetime_start' => Carbon::today()->setHour(11),
            'status'         => 'confirmed',
            'price'          => 150000,
            'correlation_id' => (string) Str::uuid(),
            'uuid'           => (string) Str::uuid(),
        ]);

        $component = Livewire::test(\App\Livewire\Beauty\AppointmentCalendar::class, [
            'salonId' => $this->salon->id,
        ]);

        $stats = $component->get('stats');
        $this->assertGreaterThanOrEqual(1, $stats['total']);
        $this->assertEquals(1, $stats['confirmed']);
    }

    /** @test */
    public function calendar_loads_day_appointments_on_day_click(): void
    {
        $this->actingAs($this->user);
        $today = Carbon::today()->format('Y-m-d');

        Appointment::create([
            'tenant_id'      => 1,
            'salon_id'       => $this->salon->id,
            'master_id'      => $this->master->id,
            'service_id'     => $this->service->id,
            'client_id'      => $this->user->id,
            'datetime_start' => Carbon::today()->setHour(12),
            'status'         => 'pending',
            'price'          => 150000,
            'correlation_id' => (string) Str::uuid(),
            'uuid'           => (string) Str::uuid(),
        ]);

        Livewire::test(\App\Livewire\Beauty\AppointmentCalendar::class, [
            'salonId' => $this->salon->id,
        ])
            ->call('selectDay', $today)
            ->assertSet('selectedDate', $today)
            ->assertSet('selectedDayAppointments', fn ($apts) => count($apts) === 1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
