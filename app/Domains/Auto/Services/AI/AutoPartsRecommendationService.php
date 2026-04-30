<?php declare(strict_types=1);

namespace App\Domains\Auto\Services\AI;

use App\Domains\Auto\Models\AutoPart;
use Illuminate\Database\DatabaseManager;

/**
 * AutoPartsRecommendationService - Recommends parts based on work list
 * 
 * Finds matching parts for repair work items.
 */
final readonly class AutoPartsRecommendationService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Recommend parts for work list
     */
    public function recommend(array $workList, int $tenantId, bool $isB2b): array
    {
        $parts = [];
        $partTypes = [];

        foreach ($workList as $workItem) {
            $type = $workItem['type'] ?? '';
            if (!in_array($type, $partTypes, true)) {
                $partTypes[] = $type;
            }
        }

        foreach ($partTypes as $partType) {
            $matchingParts = AutoPart::where('tenant_id', $tenantId)
                ->where('category', 'LIKE', "%$partType%")
                ->where('is_active', true)
                ->limit(3)
                ->get();

            foreach ($matchingParts as $part) {
                $parts[] = [
                    'id' => $part->id,
                    'name' => $part->name,
                    'sku' => $part->sku ?? '',
                    'category' => $part->category,
                    'price' => $isB2b ? $part->price * 0.80 : $part->price,
                    'original_price' => $part->price,
                    'stock_quantity' => $part->stock_quantity ?? 0,
                    'in_stock' => ($part->stock_quantity ?? 0) > 0,
                    'is_oem' => $part->is_oem ?? false,
                    'warranty_months' => $part->warranty_months ?? 12,
                    'ar_preview_url' => url("/auto/parts/ar-preview/$part->id"),
                ];
            }
        }

        return $parts;
    }
}
