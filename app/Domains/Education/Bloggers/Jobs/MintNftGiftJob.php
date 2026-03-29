<?php

declare(strict_types=1);

namespace App\Domains\Content\Bloggers\Jobs;

use App\Domains\Education\Bloggers\Models\NftGift;
use App\Domains\Education\Bloggers\Services\NftMintingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Async Job for NFT Minting on TON Blockchain
 */
class MintNftGiftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 600;

    public function __construct(
        private readonly int $giftId,
        private readonly string $correlationId = '',
    ) {
        $this->onQueue('nft-minting');
    }

    public function handle(NftMintingService $mintingService): void
    {
        $gift = NftGift::findOrFail($this->giftId);

        try {
            $mintingService->mintGift($this->giftId, $this->correlationId);

            Log::channel('bloggers')->info('NFT gift minting completed', [
                'gift_id' => $this->giftId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('bloggers')->error('NFT minting job failed', [
                'gift_id' => $this->giftId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            // Retry with exponential backoff
            if ($this->attempts() < $this->tries) {
                $this->release($this->attempts() * 60); // 1 min, 2 min, 3 min
            } else {
                // Mark as failed after 3 attempts
                $gift->update(['minting_status' => 'expired']);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('bloggers')->error('NFT minting job permanently failed', [
            'gift_id' => $this->giftId,
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);

        $gift = NftGift::find($this->giftId);
        if ($gift) {
            $gift->update([
                'minting_status' => 'expired',
                'minting_error' => 'Job failed after 3 retries: ' . $exception->getMessage(),
            ]);
        }
    }
}
