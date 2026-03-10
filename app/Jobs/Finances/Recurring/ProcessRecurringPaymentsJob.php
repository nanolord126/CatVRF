<?php

namespace App\Jobs\Finances\Recurring;

use App\Domains\Finances\Models\Subscription;
use App\Domains\Finances\Services\Recurring\RecurringPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Carbon;

class ProcessRecurringPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(RecurringPaymentService $service): void
    {
        Subscription::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('last_payment_at')->orWhere('last_payment_at', '<=', Carbon::now()->subMonth()))
            ->each(fn ($sub) => $service->processSubscription($sub));
    }
}
