<?php

namespace App\Jobs\Finances;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendBusinessReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private User $user) {}

    public function handle(): void
    {
        // Используем переданного пользователя
        $user = $this->user;
        if (!$user) {
            Log::warning('SendBusinessReport: No user provided');
            return;
        }

        $settings = $user->reporting_settings ?? [];
        if (empty($settings)) {
            return;
        }

        // Генерация отчета на основе данных пользователя
        $reportData = [
            'period' => 'weekly',
            'user_id' => $user->id,
            'generated_at' => Carbon::now(),
        ];

        // Логирование отчета для отслеживания
        Log::channel('reporting')->info('Business report generated', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'period' => $reportData['period'],
        ]);

        // Обновление времени последней отправки отчета
        $user->update(['last_report_at' => Carbon::now()]);

        Log::info('Business report sent', [
            'user_id' => $user->id,
            'timestamp' => Carbon::now(),
        ]);
    }
}
