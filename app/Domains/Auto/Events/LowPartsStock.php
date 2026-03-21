<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\AutoPart;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: запчасть кончилась (остаток < порога).
 * Production 2026.
 */
final class LowPartsStock
{
    use Dispatchable, SerializesModels;

    public function __construct(
        readonly public AutoPart $part,
        readonly public string $correlationId = '',
    ) {
    }
}
