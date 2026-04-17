<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Education;

use App\Http\Controllers\Controller;
use App\Domains\Education\Requests\CalculatePriceRequest;
use App\Domains\Education\Requests\TriggerFlashSaleRequest;
use App\Domains\Education\DTOs\CalculatePriceDto;
use App\Domains\Education\Services\EducationDynamicPricingService;
use App\Domains\Education\Resources\PriceAdjustmentResource;
use App\Domains\Education\Events\PriceUpdatedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;

final readonly class DynamicPricingController extends Controller
{
    public function __construct(
        private EducationDynamicPricingService $pricingService,
    ) {}

    public function calculate(CalculatePriceRequest $request): JsonResponse
    {
        $dto = CalculatePriceDto::from($request);

        $priceAdjustment = $this->pricingService->calculateDynamicPrice($dto);

        Event::dispatch(new PriceUpdatedEvent(
            courseId: $dto->courseId,
            tenantId: $dto->tenantId,
            businessGroupId: $dto->businessGroupId,
            priceAdjustment: $priceAdjustment,
            correlationId: $dto->correlationId,
        ));

        return (new PriceAdjustmentResource($priceAdjustment))
            ->response()
            ->setStatusCode(200)
            ->header('X-Correlation-ID', $dto->correlationId);
    }

    public function triggerFlashSale(int $courseId, \Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'discount_percent' => ['required', 'integer', 'min:1', 'max:40'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $discountPercent = (int) $request->input('discount_percent');

        $priceAdjustment = $this->pricingService->triggerFlashSale(
            courseId: $courseId,
            discountPercent: $discountPercent,
            correlationId: $correlationId,
        );

        return (new PriceAdjustmentResource($priceAdjustment))
            ->response()
            ->setStatusCode(200)
            ->header('X-Correlation-ID', $correlationId);
    }
}
