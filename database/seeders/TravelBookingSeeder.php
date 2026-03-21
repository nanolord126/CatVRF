<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Travel\Models\TravelBooking;
use Illuminate\Database\Seeder;

final class TravelBookingSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['tour_id' => 1, 'traveler_name' => 'Иван Петров', 'traveler_email' => 'ivan@mail.ru', 'traveler_phone' => '+7-900-111-1111', 'participants' => 2, 'total_price' => 500000, 'status' => 'confirmed', 'payment_status' => 'paid'],
            ['tour_id' => 2, 'traveler_name' => 'Мария Сидорова', 'traveler_email' => 'maria@mail.ru', 'traveler_phone' => '+7-900-222-2222', 'participants' => 1, 'total_price' => 350000, 'status' => 'confirmed', 'payment_status' => 'paid'],
            ['tour_id' => 1, 'traveler_name' => 'Алексей Иванов', 'traveler_email' => 'alex@mail.ru', 'traveler_phone' => '+7-900-333-3333', 'participants' => 4, 'total_price' => 850000, 'status' => 'pending', 'payment_status' => 'pending'],
            ['tour_id' => 3, 'traveler_name' => 'Светлана Петрова', 'traveler_email' => 'sveta@mail.ru', 'traveler_phone' => '+7-900-444-4444', 'participants' => 3, 'total_price' => 720000, 'status' => 'confirmed', 'payment_status' => 'paid'],
            ['tour_id' => 2, 'traveler_name' => 'Николай Смирнов', 'traveler_email' => 'nikolai@mail.ru', 'traveler_phone' => '+7-900-555-5555', 'participants' => 2, 'total_price' => 600000, 'status' => 'completed', 'payment_status' => 'paid'],
            ['tour_id' => 1, 'traveler_name' => 'Ольга Козлова', 'traveler_email' => 'olga@mail.ru', 'traveler_phone' => '+7-900-666-6666', 'participants' => 2, 'total_price' => 520000, 'status' => 'confirmed', 'payment_status' => 'paid'],
            ['tour_id' => 3, 'traveler_name' => 'Владимир Новиков', 'traveler_email' => 'vladimir@mail.ru', 'traveler_phone' => '+7-900-777-7777', 'participants' => 1, 'total_price' => 280000, 'status' => 'cancelled', 'payment_status' => 'refunded'],
            ['tour_id' => 2, 'traveler_name' => 'Надежда Волкова', 'traveler_email' => 'nadezhda@mail.ru', 'traveler_phone' => '+7-900-888-8888', 'participants' => 2, 'total_price' => 580000, 'status' => 'confirmed', 'payment_status' => 'paid'],
            ['tour_id' => 1, 'traveler_name' => 'Дмитрий Морозов', 'traveler_email' => 'dmitry@mail.ru', 'traveler_phone' => '+7-900-999-9999', 'participants' => 3, 'total_price' => 750000, 'status' => 'completed', 'payment_status' => 'paid'],
            ['tour_id' => 3, 'traveler_name' => 'Елена Сорокина', 'traveler_email' => 'elena@mail.ru', 'traveler_phone' => '+7-901-000-0000', 'participants' => 1, 'total_price' => 320000, 'status' => 'pending', 'payment_status' => 'pending'],
        ];

        foreach ($items as $item) {
            $bookingDate = now()->subDays(random_int(1, 60));
            $departureDate = now()->addDays(random_int(1, 90));
            TravelBooking::updateOrCreate(
                ['traveler_email' => $item['traveler_email'], 'tenant_id' => 1],
                array_merge($item, [
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'tenant_id' => 1,
                    'booking_date' => $bookingDate,
                    'departure_date' => $departureDate,
                ])
            );
        }
    }
}
