<?php declare(strict_types=1);

namespace App\Domains\Auto\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CarImportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => $this->resource['success'] ?? false,
            'vin' => $this->resource['vin'] ?? null,
            'declared_value' => $this->resource['declared_value'] ?? null,
            'currency' => $this->resource['currency'] ?? null,
            'exchange_rate' => $this->resource['exchange_rate'] ?? null,
            'customs_duty' => $this->resource['customs_duty'] ?? [],
            'excise_tax' => $this->resource['excise_tax'] ?? [],
            'vat' => $this->resource['vat'] ?? [],
            'recycling_fee' => $this->resource['recycling_fee'] ?? [],
            'total_duties' => $this->resource['total_duties'] ?? [],
            'restrictions' => $this->resource['restrictions'] ?? [],
            'estimated_import_cost' => $this->resource['estimated_import_cost'] ?? [],
            'correlation_id' => $this->resource['correlation_id'] ?? null,
        ];
    }
}
