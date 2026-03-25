<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Http\Controllers;

use App\Domains\Bloggers\Models\Stream;
use App\Domains\Bloggers\Models\NftGift;
use App\Domains\Bloggers\Services\NftMintingService;
use App\Domains\Bloggers\Http\Requests\SendGiftRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class GiftController
{
    public function __construct(
        private readonly NftMintingService $nftService,
    ) {}

    /**
     * Send NFT gift during stream
     */
    public function send(SendGiftRequest $request, string $roomId): JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();
            $senderId = auth()->id();

            $stream = Stream::where('room_id', $roomId)
                ->where('status', 'live')
                ->where('tenant_id', tenant()->id)
                ->firstOrFail();

            // Check if gifts are enabled
            if (!$stream->canAcceptGifts()) {
                return response()->json([
                    'message' => 'Gifts are not enabled for this stream',
                ], 400);
            }

            $gift = $this->nftService->createGift(
                streamId: (int) $stream->id,
                senderUserId: $senderId,
                recipientUserId: (int) $stream->blogger_id,
                amount: $request->integer('amount'),
                giftType: $request->string('gift_type')->value(),
                message: $request->string('message', '')->value(),
                correlationId: $correlationId,
            );

            $this->log->channel('audit')->info('NFT gift created', [
                'correlation_id' => $correlationId,
                'gift_id' => $gift->id,
                'stream_id' => $stream->id,
                'sender_id' => $senderId,
                'amount' => $request->integer('amount'),
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => $gift,
                'minting_status' => $gift->minting_status,
                'message' => 'NFT gift queued for minting',
            ], 201);
        } catch (\Exception $e) {
            $this->log->channel('fraud_alert')->warning('Gift creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Failed to send gift',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get gift status (minting progress)
     */
    public function getStatus(int $giftId): JsonResponse
    {
        try {
            $gift = NftGift::with('stream')
                ->findOrFail($giftId);

            // Check authorization
            $userId = auth()->id();
            if ($gift->sender_user_id !== $userId && 
                $gift->recipient_user_id !== $userId && 
                !auth()->user()->isAdmin()) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'data' => [
                    'gift_id' => $gift->id,
                    'minting_status' => $gift->minting_status,
                    'ton_address' => $gift->ton_address,
                    'nft_address' => $gift->nft_address,
                    'nft_token_id' => $gift->nft_token_id,
                    'ton_tx_hash' => $gift->ton_tx_hash,
                    'minted_at' => $gift->minted_at,
                    'upgrade_eligible_at' => $gift->upgrade_eligible_at,
                    'is_upgraded' => $gift->is_upgraded,
                    'explorer_url' => $gift->getTonExplorerUrl(),
                    'minting_error' => $gift->minting_error,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gift not found',
            ], 404);
        }
    }

    /**
     * Get stream gifts (recent NFT gifts received)
     */
    public function getStreamGifts(string $roomId): JsonResponse
    {
        try {
            $stream = Stream::where('room_id', $roomId)
                ->where('tenant_id', tenant()->id)
                ->firstOrFail();

            $gifts = NftGift::where('stream_id', $stream->id)
                ->where('minting_status', 'minted')
                ->with(['senderUser', 'recipientUser'])
                ->orderByDesc('minted_at')
                ->limit(100)
                ->get();

            return response()->json([
                'data' => $gifts,
                'count' => count($gifts),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch gifts',
            ], 500);
        }
    }

    /**
     * Get user's received gifts
     */
    public function getUserGifts(): JsonResponse
    {
        try {
            $userId = auth()->id();

            $gifts = NftGift::where('recipient_user_id', $userId)
                ->where('minting_status', 'minted')
                ->with('stream', 'senderUser')
                ->orderByDesc('minted_at')
                ->paginate(50);

            return response()->json([
                'data' => $gifts->items(),
                'pagination' => [
                    'total' => $gifts->total(),
                    'current_page' => $gifts->currentPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch gifts',
            ], 500);
        }
    }

    /**
     * Upgrade gift to collector NFT (after 14 days)
     */
    public function upgrade(int $giftId): JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();
            $userId = auth()->id();

            $gift = NftGift::findOrFail($giftId);

            // Check authorization
            if ($gift->recipient_user_id !== $userId) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            if (!$gift->isEligibleForUpgrade()) {
                return response()->json([
                    'message' => 'Gift is not eligible for upgrade yet',
                    'eligible_at' => $gift->upgrade_eligible_at,
                ], 400);
            }

            $gift = $this->nftService->upgradeToCollectorNft(
                giftId: $giftId,
                correlationId: $correlationId,
            );

            $this->log->channel('audit')->info('NFT gift upgraded', [
                'correlation_id' => $correlationId,
                'gift_id' => $giftId,
                'user_id' => $userId,
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => $gift,
                'message' => 'Gift upgraded to collector NFT',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upgrade gift',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Retry minting failed gift
     */
    public function retryMinting(int $giftId): JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();

            $gift = NftGift::findOrFail($giftId);

            if ($gift->minting_status !== 'failed') {
                return response()->json([
                    'message' => 'Only failed gifts can be retried',
                ], 400);
            }

            // Queue for reminting
            \App\Domains\Bloggers\Jobs\MintNftGiftJob::dispatch($gift)
                ->delay(now()->addSeconds(5))
                ->onQueue('nft-minting');

            $gift->update([
                'minting_status' => 'pending',
                'minting_error' => null,
                'correlation_id' => $correlationId,
            ]);

            $this->log->channel('audit')->info('NFT minting retried', [
                'correlation_id' => $correlationId,
                'gift_id' => $giftId,
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => $gift,
                'message' => 'Minting retried',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retry minting',
            ], 400);
        }
    }
}
