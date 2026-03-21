<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\AutoServiceOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: работы завершены (СТО).
 * Production 2026.
 */
final class RepairWorkCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        readonly public AutoServiceOrder $order,
        readonly public string $correlationId = '',
    ) {
    }
}
