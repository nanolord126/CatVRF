<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Events;

use App\Domains\HomeServices\Models\ServiceJob;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\SerializesModels;

final class ServiceJobCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ServiceJob $job, public string $correlationId) {}
}
