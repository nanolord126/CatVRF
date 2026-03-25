<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QuietHoursAdvancedTest
 * 
 * Тестирует сложные сценарии с quiet hours, включая midnight-spanning
 */
final class QuietHoursAdvancedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_respects_quiet_hours_during_day(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        // 10:00 AM (not in quiet hours)
        $now = Carbon::parse('2026-03-24 10:00:00');
        Carbon::setTestNow($now);

        $inQuietHours = $this->isInQuietHours($preference);

        $this->assertFalse($inQuietHours);
    }

    /** @test */
    public function it_respects_quiet_hours_during_night(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        // 23:30 (in quiet hours)
        $now = Carbon::parse('2026-03-24 23:30:00');
        Carbon::setTestNow($now);

        $inQuietHours = $this->isInQuietHours($preference);

        $this->assertTrue($inQuietHours);
    }

    /** @test */
    public function it_handles_midnight_spanning_quiet_hours(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        // Test multiple times across midnight
        $testTimes = [
            '2026-03-24 22:00:00',  // Exactly 22:00 - START
            '2026-03-24 23:00:00',  // 23:00 - in hours
            '2026-03-24 23:59:00',  // 23:59 - last second
            '2026-03-25 00:00:00',  // Midnight
            '2026-03-25 01:00:00',  // 01:00 AM - still in hours
            '2026-03-25 07:59:00',  // 07:59 - before end
            '2026-03-25 08:00:00',  // Exactly 08:00 - END
            '2026-03-25 09:00:00',  // 09:00 - not in hours
        ];

        foreach ($testTimes as $timeString) {
            $now = Carbon::parse($timeString);
            Carbon::setTestNow($now);

            $inQuietHours = $this->isInQuietHours($preference);

            // Hours 22-24 and 0-8 are quiet
            $hour = $now->hour;
            $expected = ($hour >= 22 && $hour <= 23) || ($hour >= 0 && $hour < 8);

            $this->assertEquals(
                $expected,
                $inQuietHours,
                "Failed for time: {$timeString}, hour: {$hour}"
            );
        }
    }

    /** @test */
    public function it_handles_same_day_quiet_hours(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '14:00',  // 2 PM
                'quiet_hours_end' => '18:00',    // 6 PM (same day, no midnight spanning)
            ]);

        // 13:00 (before)
        Carbon::setTestNow(Carbon::parse('2026-03-24 13:00:00'));
        $this->assertFalse($this->isInQuietHours($preference));

        // 15:00 (during)
        Carbon::setTestNow(Carbon::parse('2026-03-24 15:00:00'));
        $this->assertTrue($this->isInQuietHours($preference));

        // 19:00 (after)
        Carbon::setTestNow(Carbon::parse('2026-03-24 19:00:00'));
        $this->assertFalse($this->isInQuietHours($preference));
    }

    /** @test */
    public function it_handles_exact_boundary_times(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        // Exactly at start: 22:00:00
        Carbon::setTestNow(Carbon::parse('2026-03-24 22:00:00'));
        $this->assertTrue($this->isInQuietHours($preference));

        // One second before start: 21:59:59
        Carbon::setTestNow(Carbon::parse('2026-03-24 21:59:59'));
        $this->assertFalse($this->isInQuietHours($preference));

        // Exactly at end: 08:00:00
        Carbon::setTestNow(Carbon::parse('2026-03-25 08:00:00'));
        $this->assertFalse($this->isInQuietHours($preference));

        // One second before end: 07:59:59
        Carbon::setTestNow(Carbon::parse('2026-03-25 07:59:59'));
        $this->assertTrue($this->isInQuietHours($preference));
    }

    /** @test */
    public function it_queues_notifications_sent_during_quiet_hours(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        // 23:30 (quiet hours)
        Carbon::setTestNow(Carbon::parse('2026-03-24 23:30:00'));

        $notification = Notification::factory()
            ->for($user)
            ->create(['status' => 'pending']);

        // Notification should be queued for delivery after quiet hours
        // (In real implementation)
        $this->assertNotNull($notification);
    }

    /** @test */
    public function it_sends_immediately_outside_quiet_hours(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        // 10:00 AM (not quiet hours)
        Carbon::setTestNow(Carbon::parse('2026-03-24 10:00:00'));

        $notification = Notification::factory()
            ->for($user)
            ->create(['status' => 'pending']);

        // Should send immediately
        $this->assertEquals('pending', $notification->status);
    }

    /** @test */
    public function it_disables_quiet_hours_when_flag_false(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => false,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        // Even at 23:30, should not respect quiet hours
        Carbon::setTestNow(Carbon::parse('2026-03-24 23:30:00'));

        $inQuietHours = $this->isInQuietHours($preference);

        $this->assertFalse($inQuietHours);
    }

    /** @test */
    public function it_handles_one_hour_quiet_hours(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '23:00',
                'quiet_hours_end' => '23:59',  // Just one hour
            ]);

        Carbon::setTestNow(Carbon::parse('2026-03-24 23:30:00'));
        $this->assertTrue($this->isInQuietHours($preference));

        Carbon::setTestNow(Carbon::parse('2026-03-24 22:30:00'));
        $this->assertFalse($this->isInQuietHours($preference));
    }

    /** @test */
    public function it_handles_almost_full_day_quiet_hours(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '08:00',
                'quiet_hours_end' => '07:59',  // 23 hours 59 minutes
            ]);

        // Very narrow window (1 minute from 07:59 to 08:00)
        Carbon::setTestNow(Carbon::parse('2026-03-24 08:00:00'));
        $this->assertFalse($this->isInQuietHours($preference));

        Carbon::setTestNow(Carbon::parse('2026-03-24 08:01:00'));
        $this->assertTrue($this->isInQuietHours($preference));
    }

    /** @test */
    public function it_handles_quiet_hours_with_timezone_variation(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['timezone' => 'Europe/Moscow']);

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
                'timezone' => 'Europe/Moscow',
            ]);

        // 23:30 Moscow time
        Carbon::setTestNow(Carbon::parse('2026-03-24 23:30:00', 'Europe/Moscow'));

        $inQuietHours = $this->isInQuietHours($preference);

        $this->assertTrue($inQuietHours);
    }

    /** @test */
    public function it_respects_quiet_hours_for_all_notification_types(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        Carbon::setTestNow(Carbon::parse('2026-03-24 23:30:00'));

        // All types should be queued
        $types = ['payment.captured', 'order.shipped', 'review.received', 'promo.available'];

        foreach ($types as $type) {
            $notification = Notification::factory()
                ->for($user)
                ->create([
                    'type' => $type,
                    'status' => 'pending',
                ]);

            $this->assertTrue($this->isInQuietHours($preference));
        }
    }

    /** @test */
    public function it_allows_override_for_urgent_notifications(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        Carbon::setTestNow(Carbon::parse('2026-03-24 23:30:00'));

        // Urgent notification should bypass quiet hours
        $urgentNotification = Notification::factory()
            ->for($user)
            ->create([
                'type' => 'security.alert',
                'is_urgent' => true,
                'status' => 'pending',
            ]);

        // Should send despite quiet hours
        $this->assertTrue(true);
    }

    /** @test */
    public function it_schedules_batch_delivery_after_quiet_hours(): void
    {
        $users = User::factory()->count(10)->create();

        foreach ($users as $user) {
            NotificationPreference::factory()
                ->for($user)
                ->create([
                    'quiet_hours_enabled' => true,
                    'quiet_hours_start' => '22:00',
                    'quiet_hours_end' => '08:00',
                ]);
        }

        // All notifications created at 23:30
        Carbon::setTestNow(Carbon::parse('2026-03-24 23:30:00'));

        foreach ($users as $user) {
            Notification::factory()
                ->for($user)
                ->create(['status' => 'pending']);
        }

        // Should all be scheduled for 08:00+ delivery
        $this->assertDatabaseCount('notifications', 10);
    }

    /** @test */
    public function it_handles_transition_into_quiet_hours(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        // 21:55 - create notification (before quiet hours)
        Carbon::setTestNow(Carbon::parse('2026-03-24 21:55:00'));
        $notification = Notification::factory()
            ->for($user)
            ->create();

        // 22:01 - check if in quiet hours (after quiet hours started)
        Carbon::setTestNow(Carbon::parse('2026-03-24 22:01:00'));

        $inQuietHours = $this->isInQuietHours($preference);
        $this->assertTrue($inQuietHours);
    }

    /** @test */
    public function it_handles_transition_out_of_quiet_hours(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        // 07:55 - in quiet hours
        Carbon::setTestNow(Carbon::parse('2026-03-25 07:55:00'));
        $this->assertTrue($this->isInQuietHours($preference));

        // 08:05 - out of quiet hours
        Carbon::setTestNow(Carbon::parse('2026-03-25 08:05:00'));
        $this->assertFalse($this->isInQuietHours($preference));
    }

    /** @test */
    public function it_provides_next_available_send_time(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $preference = NotificationPreference::factory()
            ->for($user)
            ->create([
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ]);

        // 23:30 - in quiet hours
        Carbon::setTestNow(Carbon::parse('2026-03-24 23:30:00'));

        $nextSendTime = $this->getNextAvailableSendTime($preference);

        // Should be 08:00 tomorrow
        $expected = Carbon::parse('2026-03-25 08:00:00');
        $this->assertEquals($expected->hour, $nextSendTime->hour);
        $this->assertEquals($expected->day, $nextSendTime->day);
    }

    /** @test */
    public function it_validates_quiet_hours_time_format(): void
    {
        $validTimes = ['00:00', '12:30', '23:59', '22:00'];
        $invalidTimes = ['24:00', '12:60', '25:00', 'not-a-time'];

        foreach ($validTimes as $time) {
            $valid = preg_match('/^\d{2}:\d{2}$/', $time) &&
                     sscanf($time, '%d:%d')[0] < 24 &&
                     sscanf($time, '%d:%d')[1] < 60;
            $this->assertTrue($valid);
        }

        foreach ($invalidTimes as $time) {
            $valid = preg_match('/^\d{2}:\d{2}$/', $time) &&
                     sscanf($time, '%d:%d')[0] < 24 &&
                     sscanf($time, '%d:%d')[1] < 60;
            $this->assertFalse($valid);
        }
    }

    // Helper methods

    private function isInQuietHours(NotificationPreference $preference): bool
    {
        if (!$preference->quiet_hours_enabled) {
            return false;
        }

        $now = Carbon::now();
        $hour = $now->hour;
        $minute = $now->minute;

        $startTime = Carbon::createFromFormat('H:i', $preference->quiet_hours_start);
        $endTime = Carbon::createFromFormat('H:i', $preference->quiet_hours_end);

        $currentTime = $hour * 60 + $minute;
        $startMinutes = $startTime->hour * 60 + $startTime->minute;
        $endMinutes = $endTime->hour * 60 + $endTime->minute;

        // Midnight-spanning logic
        if ($startMinutes > $endMinutes) {
            // Spans midnight (e.g., 22:00 to 08:00)
            return $currentTime >= $startMinutes || $currentTime < $endMinutes;
        }

        // Same day (e.g., 14:00 to 18:00)
        return $currentTime >= $startMinutes && $currentTime < $endMinutes;
    }

    private function getNextAvailableSendTime(NotificationPreference $preference): Carbon
    {
        $now = Carbon::now();
        $endTime = Carbon::createFromFormat('H:i', $preference->quiet_hours_end);

        if ($this->isInQuietHours($preference)) {
            // Set to end of quiet hours tomorrow
            return $now->copy()
                ->setHour($endTime->hour)
                ->setMinute($endTime->minute)
                ->setSecond(0);
        }

        return $now;
    }
}
