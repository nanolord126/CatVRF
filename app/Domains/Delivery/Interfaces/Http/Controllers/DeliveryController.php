<?php

declare(strict_types=1);

namespace App\Domains\Delivery\Interfaces\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Domains\Delivery\Application\UseCases\CreateDeliveryUseCase;
use App\Domains\Delivery\Domain\DTOs\DeliveryData;
use App\Domains\Delivery\Domain\Entities\Delivery;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class DeliveryController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    public function store(Request $request, CreateDeliveryUseCase $createDeliveryUseCase): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $data = new DeliveryData(
                order_id: $request->input('order_id'),
                tenant_id: tenant()->id,
                courier_id: null,
                status: \App\Domains\Delivery\Domain\Enums\DeliveryStatus::PENDING,
                from_address: $request->input('from_address'),
                to_address: $request->input('to_address'),
                payload: $request->input('payload'),
                correlation_id: $correlationId
            );

            $delivery = $createDeliveryUseCase($data);

            return new \Illuminate\Http\JsonResponse($delivery, Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create delivery', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new \Illuminate\Http\JsonResponse([
                'message' => 'Failed to create delivery',
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id): JsonResponse
    {
        $delivery = Delivery::with('route')->find($id);

        if (!$delivery) {
            return new \Illuminate\Http\JsonResponse(['message' => 'Delivery not found'], Response::HTTP_NOT_FOUND);
        }

        return new \Illuminate\Http\JsonResponse($delivery);
    }
}
