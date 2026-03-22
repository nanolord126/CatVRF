<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\PortfolioItem;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PortfolioService
{
    public function __construct(
        private readonly FraudControlService $fraudControl
    ) {}

    public function addPortfolioItem(array $data, string $correlationId): PortfolioItem
    {
        return DB::transaction(function () use ($data, $correlationId) {
            $this->fraudControl->check($data, 'portfolio_item_add');

            $item = PortfolioItem::create(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            Log::channel('audit')->info('Portfolio item added', [
                'portfolio_item_id' => $item->id,
                'correlation_id' => $correlationId,
            ]);

            return $item;
        });
    }
}
