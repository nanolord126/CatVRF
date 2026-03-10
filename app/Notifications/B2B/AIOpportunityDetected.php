<?php

declare(strict_types=1);

namespace App\Notifications\B2B;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\B2BProduct;

/**
 * Final 2026 AI-ML Notification: Strict, queued, production-optimized.
 */
final class AIOpportunityDetected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly B2BProduct $product,
        private readonly array $aiData
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $savings = $this->aiData['potential_savings_per_unit'] ?? 0;
        
        return (new MailMessage)
            ->subject("AI Opportunity: Price Drop in {$this->product->name}")
            ->line("Our AI-ML 2026 Engine has detected an optimum buying window.")
            ->line("Potential Savings: {$savings} V-Coins per unit.")
            ->action('View Deal', url("/b2b/products/{$this->product->id}"));
    }

    public function toArray($notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'savings' => $this->aiData['potential_savings_per_unit'] ?? 0,
            'correlation_id' => $this->product->correlation_id ?? 'system-gen',
        ];
    }
}

