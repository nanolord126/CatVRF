<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;

use App\Domains\RealEstate\Models\SaleListing;
use Illuminate\Http\JsonResponse;

/**
 * Controller для управления объявлениями о продаже.
 * Production 2026.
 */
final class SaleListingController
{
    public function index(): JsonResponse
    {
        try {
            $listings = SaleListing::query()
                ->where('status', 'active')
                ->with('property')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $listings,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function store(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        return response()->json(['success' => false], 501);
    }

    public function destroy(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        return response()->json(['success' => false], 501);
    }
}
