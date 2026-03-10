<?php

namespace App\Jobs\Finances;

use App\Models\SettlementDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateMonthlySettlements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $lastMonth = Carbon::now()->subMonth();
        $start = $lastMonth->startOfMonth()->toDateString();
        $end = $lastMonth->endOfMonth()->toDateString();

        // Логика: Каждый тенант/организация может иметь транзакции.
        // В упрощенном виде: Собираем оборот по кошельку и генерируем Акт.
        
        // Пример для модели Wallet:
        // $amount = \bavix\LaravelWallet\Models\Transaction::whereBetween('created_at', [$start, $end])->sum('amount');
        
        // Для демонстрации: Генерируем тестовый Акт, если оборота нет (в реальном проекте - условие > 0)
        $doc = SettlementDocument::create([
            'type' => 'act',
            'number' => 'ACT-' . Carbon::now()->format('Ymd') . '-' . Str::random(4),
            'document_date' => Carbon::now(),
            'amount' => 150000.00, // В реальности - посчитанная сумма
            'status' => 'draft',
            'meta' => [
                'period_start' => $start,
                'period_end' => $end,
                'generated_at' => Carbon::now()->toDateTimeString(),
            ]
        ]);
    }
}
