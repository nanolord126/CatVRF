<?php

namespace App\Jobs\Finances;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class SendBusinessReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $settings = $user->reporting_settings ?? [];
        if (empty($settings)) return;

        // Генерация AI-отчета (заглушка для существующего сервиса)
        $summary = "Итоги недели: Рост продаж +15%, Топ товар - Цветы 'Нежность'. Прогноз: Стабильность.";
        
        // Отправка (зависит от настроек канала)
        // $user->notify(new BusinessReportNotification($summary));

        $user->update(['last_report_at' => Carbon::now()]);
    }
}
