<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Http\Controllers;

use App\Domains\HomeServices\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class ServiceCategoryController
{
    public function index(): JsonResponse
    {
        try {
            $categories = ServiceCategory::where('is_active', true)->get();
            return response()->json(['success' => true, 'data' => $categories, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch categories'], 500);
        }
    }
}
