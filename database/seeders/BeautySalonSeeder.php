<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Beauty\Models\Salon;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\BeautyService;
use App\Domains\Beauty\Models\BookingSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BeautySalonSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $businessGroupId = null;

        $salons = [
            [
                'name' => 'Эстетика Премиум',
                'address' => 'Москва, Тверская ул., 15',
                'lat' => 55.7558,
                'lon' => 37.6173,
                'tags' => ['премиум', 'стрижка', 'маникюр', 'макияж'],
            ],
            [
                'name' => 'Beauty Studio Luxe',
                'address' => 'Москва, Арбат ул., 22',
                'lat' => 55.7520,
                'lon' => 37.5876,
                'tags' => ['лакшери', 'окрашивание', 'уход'],
            ],
            [
                'name' => 'Салон Красоты Модерн',
                'address' => 'Санкт-Петербург, Невский пр., 50',
                'lat' => 59.9343,
                'lon' => 30.3351,
                'tags' => ['современный', 'контуринг', 'брови'],
            ],
            [
                'name' => 'Glamour House',
                'address' => 'Казань, ул. Баумана, 38',
                'lat' => 55.7887,
                'lon' => 49.1221,
                'tags' => ['гламур', 'вечерний макияж', 'укладка'],
            ],
            [
                'name' => 'Natural Beauty',
                'address' => 'Новосибирск, Красный пр., 36',
                'lat' => 55.0084,
                'lon' => 82.9357,
                'tags' => ['натуральный', 'органический уход', 'спа'],
            ],
        ];

        foreach ($salons as $salonData) {
            $salon = Salon::create([
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => Str::uuid()->toString(),
                'name' => $salonData['name'],
                'address' => $salonData['address'],
                'lat' => $salonData['lat'],
                'lon' => $salonData['lon'],
                'status' => 'active',
                'tags' => json_encode($salonData['tags'], JSON_THROW_ON_ERROR),
                'metadata' => json_encode([
                    'rating' => rand(40, 50) / 10,
                    'review_count' => rand(50, 500),
                    'created_via' => 'seeder',
                ], JSON_THROW_ON_ERROR),
                'is_active' => true,
            ]);

            $this->seedMasters($salon->id, $tenantId, $businessGroupId);
            $this->seedServices($salon->id, $tenantId, $businessGroupId);
            $this->seedSlots($salon->id, $tenantId, $businessGroupId);
        }

        $this->command->info('Beauty Salon Seeder completed successfully.');
    }

    private function seedMasters(int $salonId, int $tenantId, ?int $businessGroupId): void
    {
        $masterNames = [
            'Анна Петрова', 'Елена Смирнова', 'Мария Иванова', 'Ольга Козлова',
            'Наталья Попова', 'Виктория Волкова', 'Юлия Соколова', 'Дарья Лебедева',
        ];

        $specializations = ['стрижка', 'окрашивание', 'маникюр', 'макияж', 'уход', 'брови', 'укладка'];

        foreach ($masterNames as $index => $name) {
            Master::create([
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
                'salon_id' => $salonId,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => Str::uuid()->toString(),
                'name' => $name,
                'specialization' => $specializations[$index % count($specializations)],
                'rating' => rand(40, 50) / 10,
                'experience_years' => rand(3, 15),
                'status' => 'active',
                'tags' => json_encode([$specializations[$index % count($specializations)]], JSON_THROW_ON_ERROR),
                'metadata' => json_encode([
                    'portfolio_photos' => rand(10, 50),
                    'client_count' => rand(100, 1000),
                    'created_via' => 'seeder',
                ], JSON_THROW_ON_ERROR),
                'is_active' => true,
            ]);
        }
    }

    private function seedServices(int $salonId, int $tenantId, ?int $businessGroupId): void
    {
        $services = [
            ['name' => 'Женская стрижка', 'description' => 'Профессиональная стрижка с учетом типа лица', 'price' => 2500.00, 'duration_minutes' => 60],
            ['name' => 'Мужская стрижка', 'description' => 'Стильная мужская стрижка', 'price' => 1500.00, 'duration_minutes' => 45],
            ['name' => 'Окрашивание', 'description' => 'Качественное окрашивание премиум-красителями', 'price' => 5000.00, 'duration_minutes' => 120],
            ['name' => 'Маникюр', 'description' => 'Классический маникюр с покрытием', 'price' => 1200.00, 'duration_minutes' => 45],
            ['name' => 'Педикюр', 'description' => 'СПА-педикюр с уходом', 'price' => 2000.00, 'duration_minutes' => 60],
            ['name' => 'Макияж', 'description' => 'Вечерний или дневной макияж', 'price' => 1800.00, 'duration_minutes' => 40],
            ['name' => 'Уход за лицом', 'description' => 'Глубокий увлажняющий уход', 'price' => 3500.00, 'duration_minutes' => 90],
            ['name' => 'Коррекция бровей', 'description' => 'Коррекция и окрашивание бровей', 'price' => 800.00, 'duration_minutes' => 30],
        ];

        foreach ($services as $service) {
            BeautyService::create([
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
                'salon_id' => $salonId,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => Str::uuid()->toString(),
                'name' => $service['name'],
                'description' => $service['description'],
                'price' => $service['price'],
                'duration_minutes' => $service['duration_minutes'],
                'status' => 'active',
                'tags' => json_encode(['услуга'], JSON_THROW_ON_ERROR),
                'metadata' => json_encode([
                    'popularity_score' => rand(1, 100),
                    'booking_count' => rand(50, 500),
                    'created_via' => 'seeder',
                ], JSON_THROW_ON_ERROR),
                'is_active' => true,
            ]);
        }
    }

    private function seedSlots(int $salonId, int $tenantId, ?int $businessGroupId): void
    {
        $masters = Master::where('salon_id', $salonId)->get();
        $services = BeautyService::where('salon_id', $salonId)->get();

        $startDate = now()->startOfDay();
        $endDate = now()->addDays(14)->endOfDay();

        while ($startDate->lte($endDate)) {
            foreach ($masters as $master) {
                foreach ($services as $service) {
                    $slotTime = $startDate->copy()->setHour(9)->setMinute(0);

                    while ($slotTime->hour < 21) {
                        $slotEndTime = $slotTime->copy()->addMinutes($service->duration_minutes);

                        if ($slotEndTime->hour < 21) {
                            BookingSlot::create([
                                'tenant_id' => $tenantId,
                                'business_group_id' => $businessGroupId,
                                'salon_id' => $salonId,
                                'master_id' => $master->id,
                                'service_id' => $service->id,
                                'uuid' => Str::uuid()->toString(),
                                'correlation_id' => Str::uuid()->toString(),
                                'slot_date' => $slotTime->toDateString(),
                                'slot_time' => $slotTime->toDateTimeString(),
                                'duration_minutes' => $service->duration_minutes,
                                'status' => 'available',
                                'tags' => json_encode(['слот'], JSON_THROW_ON_ERROR),
                                'metadata' => json_encode([
                                    'created_via' => 'seeder',
                                    'auto_generated' => true,
                                ], JSON_THROW_ON_ERROR),
                                'is_active' => true,
                            ]);
                        }

                        $slotTime = $slotTime->copy()->addMinutes($service->duration_minutes + 15);
                    }
                }
            }

            $startDate->addDay();
        }
    }
}
