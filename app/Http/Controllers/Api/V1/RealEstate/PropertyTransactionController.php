<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\RealEstate;

use App\Http\Controllers\Controller;
use App\Domains\RealEstate\Services\PropertyTransactionService;
use App\Domains\RealEstate\DTOs\CreatePropertyDto;
use App\Domains\RealEstate\DTOs\BookViewingDto;
use App\Domains\RealEstate\Requests\BookViewingRequest;
use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\PropertyViewing;
use App\Domains\RealEstate\Resources\PropertyViewingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final class PropertyTransactionController extends Controller
{
    public function __construct(
        private readonly PropertyTransactionService $transactionService
    ) {}

    public function createProperty(Request $request): JsonResponse
    {
        try {
            $dto = CreatePropertyDto::from($request);
            $property = $this->transactionService->createPropertyWithAI(
                $dto,
                (int) $request->user()->id
            );

            return response()->json([
                'success' => true,
                'property' => [
                    'id' => $property->id,
                    'uuid' => $property->uuid,
                    'title' => $property->title,
                    'address' => $property->address,
                    'price' => $property->price,
                    'features' => $property->features,
                ],
                'correlation_id' => $dto->correlationId,
            ], 201);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Property creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create property',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bookViewing(BookViewingRequest $request): JsonResponse
    {
        try {
            $dto = BookViewingDto::from($request);
            $result = $this->transactionService->bookViewingWithHold(
                $dto->propertyId,
                $dto->userId,
                $dto->scheduledAt,
                $dto->isB2B,
                $dto->correlationId
            );

            $viewing = PropertyViewing::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'tenant_id' => $dto->tenantId,
                'business_group_id' => $dto->businessGroupId,
                'property_id' => $dto->propertyId,
                'user_id' => $dto->userId,
                'scheduled_at' => $dto->scheduledAt,
                'held_at' => now(),
                'hold_expires_at' => Carbon::parse($result['hold_expires_at']),
                'status' => 'held',
                'is_b2b' => $dto->isB2B,
                'webrtc_room_id' => $result['webrtc_room_id'],
                'correlation_id' => $dto->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'viewing' => new PropertyViewingResource($viewing),
                'hold_expires_at' => $result['hold_expires_at'],
                'webrtc_room_id' => $result['webrtc_room_id'],
                'ar_viewing_url' => $result['ar_viewing_url'],
                'virtual_tour_url' => $result['virtual_tour_url'],
                'correlation_id' => $dto->correlationId,
            ], 201);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 400);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Viewing booking failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to book viewing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function calculatePredictiveScoring(Request $request, int $propertyId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());

            $result = $this->transactionService->calculatePredictiveScoring(
                $property,
                (int) $request->user()->id,
                $correlationId
            );

            return response()->json([
                'success' => true,
                'scoring' => $result,
                'correlation_id' => $correlationId,
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Predictive scoring failed', [
                'error' => $e->getMessage(),
                'property_id' => $propertyId,
                'user_id' => $request->user()->id,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate predictive scoring',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function calculateDynamicPrice(Request $request, int $propertyId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $isB2B = $request->has('inn') && $request->has('business_card_id');
            $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());

            $result = $this->transactionService->calculateDynamicPrice(
                $property,
                $isB2B,
                $correlationId
            );

            return response()->json([
                'success' => true,
                'pricing' => $result,
                'correlation_id' => $correlationId,
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Dynamic pricing failed', [
                'error' => $e->getMessage(),
                'property_id' => $propertyId,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate dynamic price',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyDocumentsOnBlockchain(Request $request, int $propertyId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $documentHashes = $request->input('document_hashes', []);
            $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());

            if (empty($documentHashes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document hashes are required',
                ], 400);
            }

            $result = $this->transactionService->verifyDocumentsOnBlockchain(
                $property,
                $documentHashes,
                $correlationId
            );

            return response()->json([
                'success' => true,
                'verification' => $result,
                'correlation_id' => $correlationId,
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Blockchain verification failed', [
                'error' => $e->getMessage(),
                'property_id' => $propertyId,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify documents on blockchain',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function initiateEscrowPayment(Request $request, int $propertyId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $amount = (float) $request->input('amount');
            $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());

            if ($amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount must be greater than 0',
                ], 400);
            }

            $result = $this->transactionService->initiateEscrowPayment(
                $property,
                (int) $request->user()->id,
                $amount,
                $correlationId
            );

            return response()->json([
                'success' => true,
                'escrow' => $result,
                'correlation_id' => $correlationId,
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Escrow payment initiation failed', [
                'error' => $e->getMessage(),
                'property_id' => $propertyId,
                'user_id' => $request->user()->id,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate escrow payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function releaseEscrowPayment(Request $request, int $propertyId): JsonResponse
    {
        try {
            $property = Property::findOrFail($propertyId);
            $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());

            $result = $this->transactionService->releaseEscrowPayment(
                $property,
                (int) $request->user()->id,
                $correlationId
            );

            return response()->json([
                'success' => true,
                'release' => $result,
                'correlation_id' => $correlationId,
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Escrow payment release failed', [
                'error' => $e->getMessage(),
                'property_id' => $propertyId,
                'user_id' => $request->user()->id,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to release escrow payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
