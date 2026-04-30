<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use App\Domains\Supermarket\Services\B2BService;
use App\Domains\Supermarket\Models\B2BCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class B2BCatalogController
{
    public function __construct(
        private readonly B2BService $b2bService,
    ) {}

    /**
     * Получить каталог с оптовыми ценами.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'nullable|string',
            'sub_vertical' => 'nullable|string',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $company = $request->attributes->get('b2b_company');
        if (!$company) {
            throw new \RuntimeException('B2B company not found in request');
        }

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 20);

        $result = $this->b2bService->getB2BCatalog(
            companyId: $company->id,
            filters: [
                'category' => $validated['category'] ?? null,
                'sub_vertical' => $validated['sub_vertical'] ?? null,
                'search' => $validated['search'] ?? null,
            ],
            page: $page,
            perPage: $perPage,
        );

        return response()->json($result);
    }

    /**
     * Получить детальную информацию о товаре с оптовой ценой.
     */
    public function show(Request $request, int $productId): JsonResponse
    {
        $company = $request->attributes->get('b2b_company');
        if (!$company) {
            throw new \RuntimeException('B2B company not found in request');
        }

        $product = \App\Domains\Supermarket\Models\Product::findOrFail($productId);

        $wholesalePrice = $this->b2bService->getWholesalePrice(
            product: $product,
            quantity: 1,
            company: $company,
        );

        $discountPercent = $product->price > 0
            ? round((($product->price - $wholesalePrice) / $product->price) * 100, 1)
            : 0;

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
            'sub_vertical' => $product->sub_vertical,
            'retail_price' => $product->price,
            'wholesale_price' => $wholesalePrice,
            'discount_percent' => $discountPercent,
            'image_url' => $product->image_url,
            'requires_cold_chain' => $product->requires_cold_chain,
            'is_active' => $product->is_active,
        ]);
    }
}
