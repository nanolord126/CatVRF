<?php

namespace App\Jobs\Finances;

use App\Domains\Finances\Models\PaymentTransaction;
use App\Domains\Finances\Services\Fiscal\FiscalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFiscalReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3; public int $retryAfter = 600;

    public function __construct(protected PaymentTransaction $tx) {}

    public function handle(FiscalService $fiscal): void
    {
        $items = $this->prepareItems($this->tx);
        $res = $fiscal->processFiscalization($this->tx->toArray(), $items);
        $this->tx->update($res);
        $this->notifyClient();
    }

    private function prepareItems($tx): array { return [['label' => 'Service', 'price' => $tx->amount, 'quantity' => 1]]; }
    private function notifyClient(): void { 
        $user = \App\Models\User::find($this->tx->user_id);
        if ($user) {
            $user->notify(new \App\Notifications\Finances\ReceiptAvailableNotification($this->tx));
        }
        // Log to internal audit for reconciliation
        \Illuminate\Support\Facades\Log::info("Receipt {$this->tx->fiscal_number} sent to User/Tenant", [
            'tx_id' => $this->tx->id,
            'correlation_id' => $this->tx->correlation_id
        ]);
    }
}
