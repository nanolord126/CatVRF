<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Factories\Beauty\AppointmentFactory;
use Database\Factories\Beauty\BeautyConsumableFactory;
use Database\Factories\Beauty\BeautyProductFactory;
use Database\Factories\Beauty\BeautySalonFactory;
use Database\Factories\Beauty\BeautyServiceFactory;
use Database\Factories\Beauty\MasterFactory;
use Database\Factories\Beauty\PortfolioItemFactory;
use Database\Factories\Beauty\ReviewFactory;
use Illuminate\Database\Seeder;

/**
 * Seeder для модуля Beauty.
 * Production 2026 - полная реализация.
 *
 * ВАЖНО: Только для тестирования! НЕ запускать в production.
 */
final class BeautySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт тестовые данные для всех сущностей модуля Beauty.
     */
    public function run(): void
    {
        // 1. Создать салоны красоты (10 штук)
        $salons = BeautySalonFactory::new()
            ->count(10)
            ->create();

        foreach ($salons as $salon) {
            // 2. Для каждого салона создать мастеров (3-7 на салон)
            $masters = MasterFactory::new()
                ->count(rand(3, 7))
                ->create([
                    'tenant_id' => $salon->tenant_id,
                    'salon_id' => $salon->id,
                ]);

            // 3. Для каждого салона создать услуги (5-15 на салон)
            $services = BeautyServiceFactory::new()
                ->count(rand(5, 15))
                ->create([
                    'tenant_id' => $salon->tenant_id,
                    'salon_id' => $salon->id,
                    'master_id' => $masters->random()->id,
                ]);

            // 4. Для каждого салона создать расходники (10-20 на салон)
            BeautyConsumableFactory::new()
                ->count(rand(10, 20))
                ->create([
                    'tenant_id' => $salon->tenant_id,
                    'salon_id' => $salon->id,
                ]);

            // 5. Для каждого салона создать товары для продажи (5-10 на салон)
            BeautyProductFactory::new()
                ->count(rand(5, 10))
                ->create([
                    'tenant_id' => $salon->tenant_id,
                    'salon_id' => $salon->id,
                ]);

            // 6. Для каждого мастера создать портфолио (3-10 работ на мастера)
            foreach ($masters as $master) {
                PortfolioItemFactory::new()
                    ->count(rand(3, 10))
                    ->create([
                        'tenant_id' => $salon->tenant_id,
                        'master_id' => $master->id,
                    ]);
            }

            // 7. Для каждого салона создать записи (10-30 на салон)
            $appointments = AppointmentFactory::new()
                ->count(rand(10, 30))
                ->create([
                    'tenant_id' => $salon->tenant_id,
                    'salon_id' => $salon->id,
                    'master_id' => $masters->random()->id,
                    'service_id' => $services->random()->id,
                ]);

            // 8. Для каждой записи создать отзыв (50% вероятность)
            foreach ($appointments as $appointment) {
                if (rand(0, 1) === 1) {
                    ReviewFactory::new()
                        ->create([
                            'tenant_id' => $salon->tenant_id,
                            'salon_id' => $salon->id,
                            'master_id' => $appointment->master_id,
                            'appointment_id' => $appointment->id,
                            'client_id' => $appointment->client_id,
                        ]);
                }
            }

            // 9. Дополнительные отзывы без записи (3-10 на салон)
            ReviewFactory::new()
                ->count(rand(3, 10))
                ->create([
                    'tenant_id' => $salon->tenant_id,
                    'salon_id' => $salon->id,
                    'master_id' => $masters->random()->id,
                ]);
        }

        $this->command->info('Beauty module seeded successfully!');
        $this->command->info("Created: {$salons->count()} salons with masters, services, appointments, reviews, and more.");
    }
}
