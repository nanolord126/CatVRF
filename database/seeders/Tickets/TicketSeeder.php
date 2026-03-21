<?php

declare(strict_types=1);

namespace Database\Seeders\Tickets;

use App\Domains\Tickets\Models\Event;
use Database\Factories\Tickets\EventFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $events = [
            [
                'name' => 'Концерт Макsim',
                'category' => 'music',
                'location' => 'Москва',
                'organizer_name' => 'Яндекс.Афиша',
                'total_capacity' => 2000,
                'min_ticket_price' => 150000,
                'rating' => 4.8,
                'review_count' => 342,
            ],
            [
                'name' => 'Лига чемпионов: Финал',
                'category' => 'sports',
                'location' => 'Санкт-Петербург',
                'organizer_name' => 'РЖД Арена',
                'total_capacity' => 5000,
                'min_ticket_price' => 200000,
                'rating' => 4.9,
                'review_count' => 567,
            ],
            [
                'name' => 'Премьера фильма "Триумф"',
                'category' => 'cinema',
                'location' => 'Москва',
                'organizer_name' => 'Кинотеатр Октябрь',
                'total_capacity' => 500,
                'min_ticket_price' => 50000,
                'rating' => 4.5,
                'review_count' => 123,
            ],
            [
                'name' => 'TechCon 2026',
                'category' => 'conference',
                'location' => 'Москва',
                'organizer_name' => 'РВК',
                'total_capacity' => 3000,
                'min_ticket_price' => 100000,
                'rating' => 4.7,
                'review_count' => 289,
            ],
            [
                'name' => 'Балет "Лебединое озеро"',
                'category' => 'theater',
                'location' => 'Москва',
                'organizer_name' => 'Большой театр',
                'total_capacity' => 1800,
                'min_ticket_price' => 300000,
                'rating' => 4.9,
                'review_count' => 456,
                'require_age_check' => false,
            ],
            [
                'name' => 'Фестиваль света Geek Picnic',
                'category' => 'festival',
                'location' => 'Москва',
                'organizer_name' => 'Geek Picnic',
                'total_capacity' => 10000,
                'min_ticket_price' => 80000,
                'rating' => 4.6,
                'review_count' => 678,
            ],
            [
                'name' => 'Мастер-класс "React Advanced"',
                'category' => 'workshop',
                'location' => 'Санкт-Петербург',
                'organizer_name' => 'ITMozg',
                'total_capacity' => 200,
                'min_ticket_price' => 30000,
                'rating' => 4.4,
                'review_count' => 89,
            ],
            [
                'name' => 'Stand-up comedy night',
                'category' => 'music',
                'location' => 'Москва',
                'organizer_name' => 'комеди-клуб',
                'total_capacity' => 600,
                'min_ticket_price' => 100000,
                'rating' => 4.3,
                'review_count' => 234,
                'require_age_check' => true,
            ],
            [
                'name' => 'Выставка Ван Гога',
                'category' => 'festival',
                'location' => 'Санкт-Петербург',
                'organizer_name' => 'Эрмитаж',
                'total_capacity' => 2000,
                'min_ticket_price' => 60000,
                'rating' => 4.8,
                'review_count' => 501,
            ],
            [
                'name' => 'eMasters 2026 Finals',
                'category' => 'sports',
                'location' => 'Москва',
                'organizer_name' => 'ESL',
                'total_capacity' => 3000,
                'min_ticket_price' => 120000,
                'rating' => 4.7,
                'review_count' => 412,
            ],
        ];

        foreach ($events as $eventData) {
            Event::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => $eventData['name'],
                ],
                [
                    'slug' => Str::slug($eventData['name']),
                    'description' => 'Мероприятие высокого качества с известными артистами и профессиональной организацией.',
                    'category' => $eventData['category'],
                    'location' => $eventData['location'],
                    'address' => $eventData['location'] . ', Центральный район',
                    'organizer_name' => $eventData['organizer_name'],
                    'organizer_phone' => '+7 (495) 123-45-67',
                    'organizer_email' => 'info@' . Str::slug($eventData['organizer_name']) . '.ru',
                    'start_datetime' => now()->addDays(rand(7, 60)),
                    'end_datetime' => now()->addDays(rand(7, 60))->addHours(3),
                    'total_capacity' => $eventData['total_capacity'],
                    'sold_count' => rand(100, (int) ($eventData['total_capacity'] * 0.7)),
                    'min_ticket_price' => $eventData['min_ticket_price'],
                    'rating' => $eventData['rating'],
                    'review_count' => $eventData['review_count'],
                    'is_online' => false,
                    'require_age_check' => $eventData['require_age_check'] ?? false,
                    'min_age' => $eventData['require_age_check'] ? 18 : 0,
                    'status' => 'published',
                    'correlation_id' => Str::uuid()->toString(),
                    'tags' => ['popular', strtolower($eventData['category'])],
                ]
            );
        }
    }
}
