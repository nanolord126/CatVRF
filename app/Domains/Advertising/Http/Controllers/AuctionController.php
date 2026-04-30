<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Http\Controllers;

use App\Domains\Advertising\Domain\Interfaces\AuctionRepositoryInterface;
use App\Domains\Advertising\Domain\Interfaces\BidRepositoryInterface;
use App\Domains\Advertising\Domain\Services\AuctionService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Auction API Controller
 *
 * Handles API endpoints for auctions and bidding.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class AuctionController
{
    public function __construct(
        private readonly AuctionRepositoryInterface $auctionRepository,
        private readonly BidRepositoryInterface $bidRepository,
        private readonly AuctionService $auctionService,
        private readonly FraudControlService $fraudService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:upcoming,active,closed,cancelled',
            'type' => 'nullable|in:forward,dutch,sealed_bid',
            'tenant_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $auctions = $this->auctionRepository->findByTenant(
            $request->input('tenant_id', 0)
        );

        if ($request->has('status')) {
            $auctions = $auctions->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $auctions = $auctions->where('type', $request->input('type'));
        }

        return response()->json([
            'data' => $auctions->map(fn ($auction) => [
                'id' => $auction->uuid,
                'name' => $auction->name,
                'type' => $auction->type,
                'status' => $auction->status,
                'starting_price' => $auction->starting_price,
                'current_price' => $auction->current_price,
                'reserve_price' => $auction->reserve_price,
                'start_at' => $auction->start_at->toIso8601String(),
                'end_at' => $auction->end_at->toIso8601String(),
                'bid_count' => count($auction->bid_history),
            ]),
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $auction = $this->auctionRepository->findByUuid($uuid);

        if ($auction === null) {
            return response()->json(['error' => 'Auction not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $auction->uuid,
                'name' => $auction->name,
                'type' => $auction->type,
                'status' => $auction->status,
                'starting_price' => $auction->starting_price,
                'current_price' => $auction->current_price,
                'reserve_price' => $auction->reserve_price,
                'start_at' => $auction->start_at->toIso8601String(),
                'end_at' => $auction->end_at->toIso8601String(),
                'bid_history' => $auction->bid_history,
            ],
        ]);
    }

    public function bid(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer',
            'bidder_id' => 'required|integer',
            'amount' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $auction = $this->auctionRepository->findByUuid($uuid);

        if ($auction === null) {
            return response()->json(['error' => 'Auction not found'], 404);
        }

        try {
            $bid = $this->auctionService->placeBid(
                auctionId: $auction->id,
                tenantId: $request->input('tenant_id'),
                bidderId: $request->input('bidder_id'),
                amount: $request->input('amount'),
                userId: $request->user()?->id ?? 0,
            );

            return response()->json([
                'data' => [
                    'bid_id' => $bid->uuid,
                    'amount' => $bid->amount,
                    'status' => $bid->status,
                    'placed_at' => $bid->placed_at->toIso8601String(),
                ],
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function bids(string $uuid): JsonResponse
    {
        $auction = $this->auctionRepository->findByUuid($uuid);

        if ($auction === null) {
            return response()->json(['error' => 'Auction not found'], 404);
        }

        $bids = $this->bidRepository->findByAuction($auction->id);

        return response()->json([
            'data' => $bids->map(fn ($bid) => [
                'id' => $bid->uuid,
                'bidder_id' => $bid->bidder_id,
                'amount' => $bid->amount,
                'status' => $bid->status,
                'placed_at' => $bid->placed_at->toIso8601String(),
            ]),
        ]);
    }

    public function close(string $uuid): JsonResponse
    {
        $auction = $this->auctionRepository->findByUuid($uuid);

        if ($auction === null) {
            return response()->json(['error' => 'Auction not found'], 404);
        }

        try {
            $this->auctionService->closeAuction($auction->id);

            return response()->json(['message' => 'Auction closed']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
