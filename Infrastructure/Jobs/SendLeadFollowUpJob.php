<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Jobs;

use Modules\CatCRM\Domain\Entities\B2BLead;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendLeadFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $leadId,
        public readonly string $message,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $lead = B2BLead::findOrFail($this->leadId);
        
        if ($lead->assigned_to_id) {
            $notificationService->send([
                'user_id' => $lead->assigned_to_id,
                'type' => 'lead_follow_up',
                'title' => "Follow-up: {$lead->company_name}",
                'message' => $this->message,
                'data' => [
                    'lead_id' => $lead->id,
                    'company' => $lead->company_name,
                ],
            ]);
        }
    }
}
