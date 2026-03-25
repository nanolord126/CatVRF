<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Services;

use App\Domains\Bloggers\Jobs\MintNftGiftJob;
use App\Domains\Bloggers\Models\NftGift;
use App\Services\FraudControlService;
use App\Services\RateLimiterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * NFT Minting Service (TON Blockchain)
 * - Создание/минт NFT подарков на TON
 * - Управление метаданными
 * - Асинхронная обработка через jobs
 * - Redis-лок для защиты от race conditions
 */
class NftMintingService
{
    private const REDIS_LOCK_TIMEOUT = 30;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly RateLimiterService $rateLimiter,
    ) {}

    /**
     * Создать и минтить NFT подарок
     */
    public function createGift(
        int $streamId,
        int $senderUserId,
        int $recipientUserId,
        string $giftName,
        string $giftImageUrl,
        int $giftPriceKopiykas,
        string $recipientTonAddress,
        string $giftType = 'emoji',
        string $correlationId = '',
    ): NftGift {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // Rate limiting
        if (! $this->rateLimiter->allow('gift:' . $senderUserId, config('bloggers.rate_limit.send_gift'))) {
            throw new \RuntimeException('Gift rate limit exceeded');
        }

        // Fraud check
        $this->fraudControl->check([
            'operation_type' => 'nft_gift_send',
            'user_id' => $senderUserId,
            'amount' => $giftPriceKopiykas,
            'correlation_id' => $correlationId,
        ]);

        // Validate price
        $minPrice = config('bloggers.nft_gifts.min_gift_price');
        $maxPrice = config('bloggers.nft_gifts.max_gift_price');
        if ($giftPriceKopiykas < $minPrice || $giftPriceKopiykas > $maxPrice) {
            throw new \InvalidArgumentException(
                "Gift price must be between {$minPrice} and {$maxPrice} kopiykas"
            );
        }

        return $this->db->transaction(function () use (
            $streamId,
            $senderUserId,
            $recipientUserId,
            $giftName,
            $giftImageUrl,
            $giftPriceKopiykas,
            $recipientTonAddress,
            $giftType,
            $correlationId,
        ) {
            // Create NFT gift record
            $gift = NftGift::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'stream_id' => $streamId,
                'sender_user_id' => $senderUserId,
                'recipient_user_id' => $recipientUserId,
                'business_group_id' => filament()?->getTenant()?->active_business_group?->id,
                'gift_name' => $giftName,
                'gift_image_url' => $giftImageUrl,
                'gift_description' => null,
                'gift_price' => $giftPriceKopiykas / 100, // Convert to rubles
                'gift_type' => $giftType,
                'ton_address' => $recipientTonAddress,
                'minting_status' => 'pending',
                'correlation_id' => $correlationId,
            ]);

            $this->log->channel('audit')->info('NFT gift created', [
                'gift_id' => $gift->id,
                'sender_id' => $senderUserId,
                'price' => $giftPriceKopiykas,
                'correlation_id' => $correlationId,
            ]);

            // Queue minting job if auto-mint enabled
            if (config('bloggers.nft_gifts.auto_mint_enabled')) {
                MintNftGiftJob::dispatch($gift->id, $correlationId)
                    ->onQueue('nft-minting')
                    ->delay(now()->addSeconds(5));
            }

            return $gift;
        });
    }

    /**
     * Минтить NFT (основная логика)
     */
    public function mintGift(int $giftId, string $correlationId = ''): NftGift
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $gift = NftGift::findOrFail($giftId);

        // Redis lock для защиты от race conditions
        $lockKey = "nft_gift_minting:{$giftId}";
        $lockId = Str::random();

        try {
            // Acquire lock
            if (! Redis::set($lockKey, $lockId, 'EX', self::REDIS_LOCK_TIMEOUT, 'NX')) {
                throw new \RuntimeException('Another minting process is already running for this gift');
            }

            return $this->db->transaction(function () use ($gift, $correlationId, $lockKey) {
                $gift->update([
                    'minting_status' => 'minting',
                    'correlation_id' => $correlationId,
                ]);

                try {
                    // $tonClient = new TonClient(...);
                    // $nft = $tonClient->mintNft(...)
                    // $gift->update(['nft_address' => $nft->address, ...])

                    // For now, simulate successful minting
                    $simulatedNftAddress = 'EQD' . Str::random(44);
                    $simulatedTxHash = Str::random(64);

                    $gift->update([
                        'minting_status' => 'minted',
                        'minted_at' => now(),
                        'nft_address' => $simulatedNftAddress,
                        'ton_tx_hash' => $simulatedTxHash,
                        'upgrade_eligible_at' => now()->addDays(14),
                    ]);

                    $this->log->channel('audit')->info('NFT gift minted', [
                        'gift_id' => $gift->id,
                        'nft_address' => $simulatedNftAddress,
                        'ton_tx_hash' => $simulatedTxHash,
                        'correlation_id' => $correlationId,
                    ]);

                    return $gift;
                } catch (\Throwable $e) {
                    $gift->update([
                        'minting_status' => 'failed',
                        'minting_error' => $e->getMessage(),
                    ]);

                    $this->log->channel('bloggers')->error('NFT minting failed', [
                        'gift_id' => $gift->id,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);

                    throw $e;
                }
            });
        } finally {
            // Release lock
            Redis::del($lockKey);
        }
    }

    /**
     * Обновить подарок на collector NFT (после 14 дней)
     */
    public function upgradeToCollectorNft(int $giftId, string $correlationId = ''): NftGift
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $gift = NftGift::findOrFail($giftId);

        if (! $gift->isEligibleForUpgrade()) {
            throw new \RuntimeException('Gift is not eligible for upgrade yet');
        }

        return $this->db->transaction(function () use ($gift, $correlationId) {

            $gift->update([
                'is_upgraded' => true,
                'upgraded_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->log->channel('audit')->info('NFT gift upgraded to collector', [
                'gift_id' => $gift->id,
                'correlation_id' => $correlationId,
            ]);

            return $gift;
        });
    }

    /**
     * Получить метаданные подарка для IPFS
     */
    public function buildMetadata(NftGift $gift): array
    {
        return [
            'name' => $gift->gift_name,
            'description' => $gift->gift_description ?? 'NFT gift from stream',
            'image' => $gift->gift_image_url,
            'attributes' => [
                [
                    'trait_type' => 'Type',
                    'value' => $gift->gift_type,
                ],
                [
                    'trait_type' => 'Stream ID',
                    'value' => (string) $gift->stream_id,
                ],
                [
                    'trait_type' => 'Sent At',
                    'value' => $gift->created_at->toIso8601String(),
                ],
            ],
            'external_url' => route('streams.show', $gift->stream_id),
        ];
    }

    /**
     * Получить неудачные попытки минта для повтора
     */
    public function getFailedMintAttempts(): \Illuminate\Database\Eloquent\Collection
    {
        return NftGift::where('minting_status', 'failed')
            ->where('created_at', '>', now()->subHours(24))
            ->get();
    }
}
