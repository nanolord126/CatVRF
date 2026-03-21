<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Venue;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Gym;
use App\Models\Coach;
use App\Models\TrainingSchedule;
use App\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Вертикали маркетплейса (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class MarketplaceVerticalsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        // --- Events ---
        $venue = Venue::create([
            'name' => 'Cyber Arena VR-2026',
            'address' => 'Innovation Blvd, 101',
            'capacity' => 500,
            'geo_location' => json_encode(['lat' => 55.7558, 'lng' => 37.6173]),
            'hall_layout' => json_encode(['rows' => 20, 'cols' => 25]),
        ]);

        $event = Event::create([
            'venue_id' => $venue->id,
            'title' => 'Global AI Sumit & Expo',
            'description' => 'The largest AI event in the ecosystem.',
            'start_at' => now()->addDays(10)->setHour(10),
            'end_at' => now()->addDays(10)->setHour(18),
            'status' => 'published',
            'seating_data' => ['type' => 'tiered', 'sectors' => ['A', 'B', 'VIP']],
        ]);

        Ticket::create([
            'event_id' => $event->id,
            'category' => 'VIP',
            'price' => 250.00,
            'quantity_available' => 50,
        ]);

        // --- Sports ---
        $gym = Gym::create([
            'name' => 'Neo-Fitness Hub',
            'address' => 'Quantum Street, 42',
            'geo_location' => ['lat' => 55.7512, 'lng' => 37.6297],
            'occupancy_data' => [
                'monday' => ['08:00' => 45, '12:00' => 80, '18:00' => 95],
                'tuesday' => ['08:00' => 30, '12:00' => 60, '18:00' => 85]
            ],
        ]);

        $coach = Coach::create([
            'user_id' => $user->id,
            'specialization' => 'AI-Driven Bio-hacking',
            'bio' => 'Expert in personalized physical optimization.',
            'hourly_rate' => 120.00,
        ]);

        TrainingSchedule::create([
            'gym_id' => $gym->id,
            'coach_id' => $coach->id,
            'training_type' => 'HIIT Intensity',
            'start_at' => now()->addDays(2)->setHour(9),
            'end_at' => now()->addDays(2)->setHour(10),
            'max_participants' => 15,
        ]);

        // --- Education ---
        $course = Course::create([
            'title' => 'Mastering AI Ecosystems 2026',
            'description' => 'Learn how to manage multi-tenant infrastructures with delegated AI.',
            'category' => 'Programming',
            'price' => 499.00,
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1: Infrastructure Foundations',
            'sort_order' => 1,
        ]);

        Lesson::create([
            'module_id' => $module->id,
            'title' => 'Lesson 1.1: Multi-tenant Scoping Logic',
            'video_url' => 'https://cdn.catvrf.io/v/infra-101.mp4',
            'content' => 'Detailed guide on schema-per-tenant isolation.',
            'sort_order' => 1,
        ]);
    }
}
