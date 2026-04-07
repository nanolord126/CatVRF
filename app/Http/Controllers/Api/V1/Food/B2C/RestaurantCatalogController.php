<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Food\B2C;

use App\Domains\Food\Infrastructure\Persistence\Eloquent\Models\RestaurantModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * Class RestaurantCatalogController
 *
 * API Controller following CatVRF canon:
 * - Constructor injection for all dependencies
 * - Request validation via Form Requests
 * - Response via ResponseFactory DI
 * - correlation_id in all responses
 *
 * @see \App\Http\Controllers\BaseApiController
 * @package App\Http\Controllers\Api\V1\Food\B2C
 */
final class RestaurantCatalogController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $response,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        // In a real app, this would use a dedicated UseCase from the Application layer
        // to apply complex filtering, sorting, and pagination.
        // For simplicity, we query the model directly here.

        $restaurants = RestaurantModel::query()
            ->where('status', 'active')
            // This is a simplified tenant scoping. Use global scopes or a dedicated service.
            ->where('tenant_id', $request->header('X-Tenant-ID'))
            ->select(['id', 'name', 'description', 'address', 'rating', 'review_count'])
            ->paginate(20);

        return $this->response->json($restaurants);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request): JsonResponse
    {
        // Again, this should go through an Application layer UseCase.
        $restaurant = RestaurantModel::query()
            ->where('tenant_id', $request->header('X-Tenant-ID'))
            ->with([
                // Eager load menu structure. This is a placeholder for a more complex query.
                // 'menuSections.dishes.modifiers'
            ])
            ->findOrFail($id);

        return $this->response->json($restaurant);
    }
}
