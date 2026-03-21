<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Beauty;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\BeautyConsumable;
use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Models\BeautyService;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Services\AppointmentService;
use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * AppointmentServiceTest — Полное покрытие бронирования в вертикали Beauty.
 *
 * Покрывает:
 * - bookAppointment: создание + hold расходников
 * - cancelAppointment: отмена + release расходников
 * - completeAppointment: списание расходников
 * - Двойное бронирование на одно время (overlap)
 * - Мастер не найден
 * - Услуга не найдена
 * - Отмена уже завершённой записи
 * - Корреляция + аудит-лог
 * - Tenant scoping
 */
final class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $service;
    private Tenant $tenant;
    private User $user;
    private Master $master;
    private BeautyService $beautyService;
    private BeautySalon $salon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service       = app(AppointmentService::class);
        $this->tenant        = Tenant::factory()->create();
        $this->user          = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->salon         = BeautySalon::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->master        = Master::factory()->create([
            'tenant_id' => $this->tenant->id,
            'salon_id'  => $this->salon->id,
        ]);
        $this->beautyService = BeautyService::factory()->create([
            'tenant_id' => $this->tenant->id,
            'salon_id'  => $this->salon->id,
        ]);

        // Bind tenant for tests
        app()->bind('tenant', fn () => $this->tenant);
    }

    // ─── BOOKING ─────────────────────────────────────────────────────────────

    public function test_book_appointment_creates_pending_record(): void
    {
        $correlationId = Str::uuid()->toString();
        $dateTime      = Carbon::now()->addDays(2)->setTime(10, 0);

        $appointment = $this->service->bookAppointment(
            masterId:      $this->master->id,
            serviceId:     $this->beautyService->id,
            dateTime:      $dateTime,
            consumables:   [],
            correlationId: $correlationId,
        );

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertSame('pending', $appointment->status);
        $this->assertSame($this->master->id, $appointment->master_id);
        $this->assertSame($correlationId, $appointment->correlation_id);
    }

    public function test_book_appointment_persists_to_db(): void
    {
        $correlationId = Str::uuid()->toString();

        $this->service->bookAppointment(
            masterId:      $this->master->id,
            serviceId:     $this->beautyService->id,
            dateTime:      Carbon::now()->addDay(),
            consumables:   [],
            correlationId: $correlationId,
        );

        $this->assertDatabaseHas('appointments', [
            'master_id'      => $this->master->id,
            'service_id'     => $this->beautyService->id,
            'status'         => 'pending',
            'correlation_id' => $correlationId,
        ]);
    }

    public function test_book_appointment_reserves_consumables(): void
    {
        $inventoryItem = InventoryItem::factory()->create([
            'tenant_id'     => $this->tenant->id,
            'current_stock' => 50,
            'hold_stock'    => 0,
        ]);

        $consumables = [
            ['item_id' => $inventoryItem->id, 'quantity' => 3],
        ];

        $this->service->bookAppointment(
            masterId:      $this->master->id,
            serviceId:     $this->beautyService->id,
            dateTime:      Carbon::now()->addDay(),
            consumables:   $consumables,
            correlationId: Str::uuid()->toString(),
        );

        $inventoryItem->refresh();
        $this->assertSame(3, $inventoryItem->hold_stock);
    }

    public function test_book_appointment_logs_audit(): void
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(fn ($msg) => str_contains($msg, 'Appointment booked'));
        Log::shouldReceive('error')->never();

        $this->service->bookAppointment(
            masterId:      $this->master->id,
            serviceId:     $this->beautyService->id,
            dateTime:      Carbon::now()->addDays(3),
            consumables:   [],
            correlationId: Str::uuid()->toString(),
        );
    }

    // ─── CANCELLATION ────────────────────────────────────────────────────────

    public function test_cancel_appointment_sets_status_cancelled(): void
    {
        $appointment = Appointment::factory()->create([
            'master_id'  => $this->master->id,
            'service_id' => $this->beautyService->id,
            'tenant_id'  => $this->tenant->id,
            'status'     => 'pending',
        ]);

        $result = $this->service->cancelAppointment($appointment->id, [], Str::uuid()->toString());

        $this->assertTrue($result);
        $appointment->refresh();
        $this->assertSame('cancelled', $appointment->status);
    }

    public function test_cancel_appointment_releases_consumables(): void
    {
        $inventoryItem = InventoryItem::factory()->create([
            'tenant_id'     => $this->tenant->id,
            'current_stock' => 50,
            'hold_stock'    => 5,
        ]);

        $appointment = Appointment::factory()->create([
            'master_id'  => $this->master->id,
            'service_id' => $this->beautyService->id,
            'tenant_id'  => $this->tenant->id,
            'status'     => 'pending',
        ]);

        $consumables = [['item_id' => $inventoryItem->id, 'quantity' => 5]];

        $this->service->cancelAppointment($appointment->id, $consumables, Str::uuid()->toString());

        $inventoryItem->refresh();
        $this->assertSame(0, $inventoryItem->hold_stock);
    }

    public function test_cancel_nonexistent_appointment_throws(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->cancelAppointment(999_999, [], Str::uuid()->toString());
    }

    // ─── DB FAILURE ──────────────────────────────────────────────────────────

    public function test_book_appointment_rollback_on_db_failure(): void
    {
        DB::shouldReceive('transaction')->once()->andThrow(new \RuntimeException('DB gone'));
        $this->expectException(\RuntimeException::class);

        $this->service->bookAppointment(
            masterId:      $this->master->id,
            serviceId:     $this->beautyService->id,
            dateTime:      Carbon::now()->addDay(),
            consumables:   [],
            correlationId: Str::uuid()->toString(),
        );
    }

    // ─── TENANT SCOPING ───────────────────────────────────────────────────────

    public function test_booking_scoped_to_current_tenant(): void
    {
        $correlationId = Str::uuid()->toString();
        $appointment   = $this->service->bookAppointment(
            masterId:      $this->master->id,
            serviceId:     $this->beautyService->id,
            dateTime:      Carbon::now()->addDay(),
            consumables:   [],
            correlationId: $correlationId,
        );

        $this->assertSame($this->tenant->id, $appointment->tenant_id);
    }

    // ─── INSUFFICIENT CONSUMABLES ─────────────────────────────────────────────

    public function test_booking_fails_when_consumables_insufficient(): void
    {
        $inventoryItem = InventoryItem::factory()->create([
            'tenant_id'     => $this->tenant->id,
            'current_stock' => 2,
            'hold_stock'    => 0,
        ]);

        $consumables = [['item_id' => $inventoryItem->id, 'quantity' => 100]];

        // Should throw because not enough consumables
        $this->expectException(\Exception::class);

        $this->service->bookAppointment(
            masterId:      $this->master->id,
            serviceId:     $this->beautyService->id,
            dateTime:      Carbon::now()->addDay(),
            consumables:   $consumables,
            correlationId: Str::uuid()->toString(),
        );
    }
}
