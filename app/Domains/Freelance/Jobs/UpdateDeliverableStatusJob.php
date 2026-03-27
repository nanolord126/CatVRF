<?php

declare(strict_types=1);


namespace App\Domains\Freelance\Jobs;

use App\Domains\Freelance\Models\FreelanceDeliverable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * UpdateDeliverableStatusJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class UpdateDeliverableStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $deliverableId = 0,
        public readonly string $correlationId = '',
    ) {
        $this->onQueue('default');

    }

    public function handle(): void
    {
        $deliverable = FreelanceDeliverable::find($this->deliverableId);
        if (!$deliverable) {
            Log::channel('audit')->warning('Deliverable not found', [
                'deliverable_id' => $this->deliverableId,
                'correlation_id' => $this->correlationId,
            ]);
            return;
        }

        if ($deliverable->status === 'submitted' && $deliverable->created_at->addDays(7)->isPast()) {
            $deliverable->update(['status' => 'pending']);
            
            Log::channel('audit')->info('Deliverable status auto-reset to pending after 7 days', [
                'deliverable_id' => $this->deliverableId,
                'correlation_id' => $this->correlationId,
            ]);
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}

