<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Staff\Models\StaffSchedule;
use Modules\Staff\Models\StaffTask;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Сотрудники (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class StaffSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create 5 workers
        $workers = User::where('is_active', true)->limit(5)->get();
        
        if ($workers->count() < 5) {
            $workers = User::factory()->count(5)->create(['is_active' => true]);
        }

        $nextWeek = Carbon::now()->startOfWeek()->addWeek();

        foreach ($workers as $worker) {
            $correlationId = (string) Str::uuid();

            // Create shifts for 5 days
            for ($i = 0; $i < 5; $i++) {
                $date = $nextWeek->copy()->addDays($i);
                
                StaffSchedule::updateOrCreate(
                    [
                        'user_id' => $worker->id,
                        'date' => $date->format('Y-m-d'),
                    ],
                    [
                        'start_time' => '09:00:00',
                        'end_time' => '18:00:00',
                        'correlation_id' => $correlationId,
                    ]
                );
            }

            // Create some tasks
            StaffTask::create([
                'user_id' => $worker->id,
                'title' => "Weekly Report for {$worker->name}",
                'description' => "Prepare and submit the weekly progress report.",
                'status' => 'TODO',
                'priority' => 'medium',
                'taskable_id' => $worker->id,
                'taskable_type' => User::class,
                'correlation_id' => $correlationId,
            ]);
        }
    }
}
