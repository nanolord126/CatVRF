<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\PortfolioItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * PortfolioItemDeleted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PortfolioItemDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PortfolioItem $portfolioItem,
        public readonly string $correlationId
    ) {}
}
