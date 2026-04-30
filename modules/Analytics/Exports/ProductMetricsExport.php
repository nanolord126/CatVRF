<?php

declare(strict_types=1);

namespace Modules\Analytics\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\Analytics\Application\Services\SellerAnalyticsService;
use Modules\Analytics\Domain\ValueObjects\Period;

/**
 * Product Metrics Export
 *
 * Exports seller product analytics to Excel format.
 * Used by sellers to download their product performance data.
 *
 * TODO: Install maatwebsite/excel package if not already installed:
 * composer require maatwebsite/excel
 */
final class ProductMetricsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    private int $sellerId;

    private int $tenantId;

    private Period $period;

    private SellerAnalyticsService $service;

    public function __construct(int $sellerId, int $tenantId, Period $period)
    {
        $this->sellerId = $sellerId;
        $this->tenantId = $tenantId;
        $this->period = $period;
        $this->service = app(SellerAnalyticsService::class);
    }

    /**
     * Get the collection of data to export.
     */
    public function collection(): Collection
    {
        $productAnalytics = $this->service->getProductAnalytics(
            $this->sellerId,
            $this->period,
            $this->tenantId,
            1,
            10000 // Export up to 10,000 products
        );

        return collect($productAnalytics['data']);
    }

    /**
     * Map the data to the desired format.
     */
    public function map($row): array
    {
        return [
            $row['id'] ?? '',
            $row['name'] ?? 'Unknown',
            $row['category'] ?? 'N/A',
            $row['price'] ?? 0,
            $row['views'] ?? 0,
            $row['add_to_cart'] ?? 0,
            $row['purchases'] ?? 0,
            $row['revenue'] ?? 0,
            number_format($row['conversion_rate'] ?? 0, 2) . '%',
            number_format($row['cart_conversion_rate'] ?? 0, 2) . '%',
            $row['refunds'] ?? 0,
            number_format($row['refund_rate'] ?? 0, 2) . '%',
            number_format($row['avg_rating'] ?? 0, 1),
        ];
    }

    /**
     * Get the headings for the export.
     */
    public function headings(): array
    {
        return [
            'Product ID',
            'Product Name',
            'Category',
            'Price',
            'Views',
            'Add to Cart',
            'Purchases',
            'Revenue',
            'Conversion Rate',
            'Cart Conversion Rate',
            'Refunds',
            'Refund Rate',
            'Avg Rating',
        ];
    }

    /**
     * Get the sheet title.
     */
    public function title(): string
    {
        return 'Product Metrics';
    }
}
