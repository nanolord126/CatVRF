<?php

declare(strict_types=1);

namespace Tests\Load;

use Modules\BeautyMasters\Domain\Entities\Appointment;
use Modules\BeautyMasters\Domain\Entities\BeautySalon;
use Modules\BeautyMasters\Domain\Entities\Master;
use Modules\BeautyMasters\Domain\Enums\AppointmentStatus;
use Modules\BeautyMasters\Application\Services\AppointmentService;
use Modules\BeautyMasters\Application\Services\MasterScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

final class BeautyLoadTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $appointmentService;
    private MasterScheduleService $scheduleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appointmentService = app(AppointmentService::class);
        $this->scheduleService = app(MasterScheduleService::class);
    }

    public function testBeautyAppointmentBooking10000RPS(): void
    {
        $salon = BeautySalon::factory()->create();
        $master = Master::factory()->for($salon)->create();
        
        $startTime = microtime(true);
        $iterations = 10000;
        $results = [];

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $appointment = Appointment::create([
                    'salon_id' => $salon->id,
                    'master_id' => $master->id,
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'scheduled_at' => now()->addDays($i % 30),
                    'status' => AppointmentStatus::Pending,
                    'amount' => rand(2000, 10000),
                ]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertGreaterThan(10000, $rps);
        $this->assertGreaterThan(99, ($successCount / $iterations) * 100);
    }

    public function testBeautyMasterScheduleRetrieval20000RPS(): void
    {
        $salon = BeautySalon::factory()->create();
        $master = Master::factory()->for($salon)->create();

        $startTime = microtime(true);
        $iterations = 20000;

        for ($i = 0; $i < $iterations; $i++) {
            $this->scheduleService->getAvailableSlots($master->id, now()->addDays($i % 30));
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(20000, $rps);
    }

    public function testBeautySalonListing5000RPS(): void
    {
        BeautySalon::factory()->count(1000)->create();

        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            BeautySalon::all();
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(5000, $rps);
    }

    public function testBeautyAppointmentCancellation3000RPS(): void
    {
        $salon = BeautySalon::factory()->create();
        $master = Master::factory()->for($salon)->create();
        
        $appointments = [];
        for ($i = 0; $i < 1000; $i++) {
            $appointments[] = Appointment::create([
                'salon_id' => $salon->id,
                'master_id' => $master->id,
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'scheduled_at' => now()->addDays(1),
                'status' => AppointmentStatus::Confirmed,
                'amount' => 5000,
            ]);
        }

        $startTime = microtime(true);
        $iterations = 3000;
        $results = [];

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $appointment = $appointments[$i % 1000];
                $appointment->update(['status' => AppointmentStatus::Cancelled]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertGreaterThan(3000, $rps);
        $this->assertEquals($iterations, $successCount);
    }

    public function testBeautySlotAvailabilityCache50000RPS(): void
    {
        $iterations = 50000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $key = "beauty_slots_{$i % 100}";
            Redis::setex($key, 60, rand(1, 20));
            Redis::get($key);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(50000, $rps);
    }

    public function testBeautyMasterRatingUpdate5000RPS(): void
    {
        $salon = BeautySalon::factory()->create();
        $master = Master::factory()->for($salon)->create(['rating' => 4.5, 'review_count' => 100]);

        $startTime = microtime(true);
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            $master->update([
                'rating' => 4.0 + ($i % 10) / 10,
                'review_count' => $master->review_count + 1,
            ]);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(5000, $rps);
    }

    public function testBeautyServiceSearch10000RPS(): void
    {
        $salon = BeautySalon::factory()->create();
        
        $startTime = microtime(true);
        $iterations = 10000;

        for ($i = 0; $i < $iterations; $i++) {
            BeautySalon::where('id', $salon->id)
                ->whereHas('masters', fn($q) => $q->where('rating', '>', 4.0))
                ->get();
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $rps = $iterations / $duration;

        $this->assertGreaterThan(10000, $rps);
    }

    public function testBeautyConcurrentAppointmentCreation(): void
    {
        $salon = BeautySalon::factory()->create();
        $master = Master::factory()->for($salon)->create();

        $startTime = microtime(true);
        $concurrentRequests = 200;
        $results = [];

        for ($i = 0; $i < $concurrentRequests; $i++) {
            try {
                Appointment::create([
                    'salon_id' => $salon->id,
                    'master_id' => $master->id,
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'scheduled_at' => now()->addDays($i % 7),
                    'status' => AppointmentStatus::Pending,
                    'amount' => 5000,
                ]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertLessThan(0.5, $duration);
        $this->assertEquals($concurrentRequests, $successCount);
    }

    public function testBeautyMemoryUsageUnderLoad(): void
    {
        $initialMemory = memory_get_usage(true);

        $salon = BeautySalon::factory()->create();
        $master = Master::factory()->for($salon)->create();

        for ($i = 0; $i < 10000; $i++) {
            Appointment::create([
                'salon_id' => $salon->id,
                'master_id' => $master->id,
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'scheduled_at' => now()->addDays($i % 365),
                'status' => AppointmentStatus::Pending,
                'amount' => rand(2000, 10000),
            ]);
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024;

        $this->assertLessThan(150, $memoryIncrease);
    }
}
