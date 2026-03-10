<?php

namespace Database\Seeders;

use App\Domains\Events\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title' => 'Tech Conference 2026',
                'description' => 'Annual tech summit with industry leaders',
                'location' => 'Convention Center',
                'latitude' => 55.7558,
                'longitude' => 37.6173,
                'status' => 'upcoming',
                'max_attendees' => 500,
            ],
            [
                'title' => 'Networking Mixer',
                'description' => 'Meet and greet for professionals',
                'location' => 'Downtown Hotel',
                'latitude' => 55.7505,
                'longitude' => 37.6174,
                'status' => 'ongoing',
                'max_attendees' => 200,
            ],
            [
                'title' => 'Workshop: AI & ML',
                'description' => 'Hands-on workshop on machine learning',
                'location' => 'Tech Hub',
                'latitude' => 55.7614,
                'longitude' => 37.6270,
                'status' => 'completed',
                'max_attendees' => 100,
            ],
        ];

        foreach ($events as $event) {
            Event::factory()->create($event);
        }
    }
}
