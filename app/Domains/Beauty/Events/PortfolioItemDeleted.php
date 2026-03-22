<?php declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\PortfolioItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PortfolioItemDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PortfolioItem $portfolioItem,
        public readonly string $correlationId
    ) {}
}
