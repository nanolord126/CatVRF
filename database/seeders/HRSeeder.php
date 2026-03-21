<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Кадровое обеспечение (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class HRSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            // Seed User HR data
            $user->update([
                'role_code' => collect(['MASTER', 'HOUSEKEEPER', 'ADMIN'])->random(),
                'phone' => '+7911' . rand(1111111, 9999999),
                'hired_at' => now()->subMonths(rand(1, 12)),
            ]);

            // Seed Attendance for last 5 days
            for ($i = 0; $i < 5; $i++) {
                Attendance::create([
                    'user_id' => $user->id,
                    'date' => now()->subDays($i),
                    'clock_in' => now()->subDays($i)->setTime(9, rand(0, 30)),
                    'clock_out' => now()->subDays($i)->setTime(18, rand(0, 30)),
                    'status' => 'present',
                    'total_hours' => 8.5,
                    'correlation_id' => (string) Str::uuid(),
                ]);
            }

            // Seed a Leave Request
            LeaveRequest::create([
                'user_id' => $user->id,
                'type' => 'vacation',
                'start_date' => now()->addWeeks(2),
                'end_date' => now()->addWeeks(2)->addDays(5),
                'status' => 'pending',
                'correlation_id' => (string) Str::uuid(),
            ]);
        }
    }
}
