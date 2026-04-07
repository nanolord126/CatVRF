<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Controllers;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class GiftController extends Controller
{

    public function __construct(
            private readonly NftMintingService $nftService, private readonly LoggerInterface $logger) {}

        /**
         * Send NFT gift during stream
         */
        public function send(SendGiftRequest $request, string $roomId): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();
                $senderId = $request->user()?->id;

                $stream = Stream::where('room_id', $roomId)
                    ->where('status', 'live')
                    ->where('tenant_id', tenant()->id)
                    ->firstOrFail();

                // Check if gifts are enabled
                if (!$stream->canAcceptGifts()) {
                    return new \Illuminate\Http\JsonResponse([
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

                $this->logger->info('NFT gift created', [
                    'correlation_id' => $correlationId,
                    'gift_id' => $gift->id,
                    'stream_id' => $stream->id,
                    'sender_id' => $senderId,
                    'amount' => $request->integer('amount'),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $gift,
                    'minting_status' => $gift->minting_status,
                    'message' => 'NFT gift queued for minting',
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->warning('Gift creation failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
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
                $userId = $request->user()?->id;
                if ($gift->sender_user_id !== $userId &&
                    $gift->recipient_user_id !== $userId &&
                    !$request->user()->isAdmin()) {
                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Unauthorized',
                    ], 403);
                }

                return new \Illuminate\Http\JsonResponse([
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
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'data' => $gifts,
                    'count' => count($gifts),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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
                $userId = $request->user()?->id;

                $gifts = NftGift::where('recipient_user_id', $userId)
                    ->where('minting_status', 'minted')
                    ->with('stream', 'senderUser')
                    ->orderByDesc('minted_at')
                    ->paginate(50);

                return new \Illuminate\Http\JsonResponse([
                    'data' => $gifts->items(),
                    'pagination' => [
                        'total' => $gifts->total(),
                        'current_page' => $gifts->currentPage(),
                    ],
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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
                $userId = $request->user()?->id;

                $gift = NftGift::findOrFail($giftId);

                // Check authorization
                if ($gift->recipient_user_id !== $userId) {
                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Unauthorized',
                    ], 403);
                }

                if (!$gift->isEligibleForUpgrade()) {
                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Gift is not eligible for upgrade yet',
                        'eligible_at' => $gift->upgrade_eligible_at,
                    ], 400);
                }

                $gift = $this->nftService->upgradeToCollectorNft(
                    giftId: $giftId,
                    correlationId: $correlationId,
                );

                $this->logger->info('NFT gift upgraded', [
                    'correlation_id' => $correlationId,
                    'gift_id' => $giftId,
                    'user_id' => $userId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $gift,
                    'message' => 'Gift upgraded to collector NFT',
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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
                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Only failed gifts can be retried',
                    ], 400);
                }

                // Queue for reminting
                \App\Domains\Content\Bloggers\Jobs\MintNftGiftJob::dispatch($gift)
                    ->delay(Carbon::now()->addSeconds(5))
                    ->onQueue('nft-minting');

                $gift->update([
                    'minting_status' => 'pending',
                    'minting_error' => null,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('NFT minting retried', [
                    'correlation_id' => $correlationId,
                    'gift_id' => $giftId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $gift,
                    'message' => 'Minting retried',
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Failed to retry minting',
                ], 400);
            }
        }
}
