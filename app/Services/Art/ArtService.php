<?php

declare(strict_types=1);

namespace App\Services\Art;

use App\Models\Art\ArtGallery;
use App\Models\Art\Artist;
use App\Models\Art\Artwork;
use App\Models\Art\ArtOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ArtService — Production-ready management of artwork lifecycle.
 */
final readonly class ArtService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private string $correlationId = ''
    ) {
        $this->correlationId = $correlationId ?: (Request()->header('X-Correlation-ID') ?? (string) Str::uuid());
    }

    /**
     * Create a new artwork listing with high validation and safety.
     * @throws \Exception
     */
    public function registerArtwork(array $data): Artwork
    {
        $this->fraud->check(['type' => 'art_registration', 'data' => $data]);

        return DB::transaction(function () use ($data) {
            $artwork = Artwork::create(array_merge($data, [
                'correlation_id' => $this->correlationId,
                'status' => 'pending', // Default to review for security
            ]));

            Log::channel('audit')->info('Artwork registered', [
                'id' => $artwork->id,
                'uuid' => $artwork->uuid,
                'correlation_id' => $this->correlationId,
            ]);

            return $artwork;
        }, 5);
    }

    /**
     * Process a purchase for an artwork (B2C or B2B).
     * @throws \Exception
     */
    public function purchaseArtwork(int $userId, int $artworkId, string $type = 'b2c'): ArtOrder
    {
        $artwork = Artwork::findOrFail($artworkId);
        
        if ($artwork->status !== 'available') {
            throw new \Exception('Artwork is not available for purchase.');
        }

        $this->fraud->check(['type' => 'art_purchase', 'user_id' => $userId, 'artwork_id' => $artworkId]);

        return DB::transaction(function () use ($userId, $artwork, $type) {
            // Locking the artwork for the transaction duration
            $artwork->lockForUpdate()->increment('price_cents', 0); 

            $totalCents = $artwork->price_cents;
            $commissionCents = (int)($totalCents * 0.14); // Standard Platform Commission 14%

            // Debit user wallet, credit gallery/artist wallet
            $this->wallet->debit($userId, $totalCents, "Purchase of artwork: {$artwork->title}");
            $this->wallet->credit($artwork->gallery->tenant_id, $totalCents - $commissionCents, "Sale of artwork: {$artwork->title}");

            $order = ArtOrder::create([
                'user_id' => $userId,
                'artwork_id' => $artwork->id,
                'type' => $type,
                'total_cents' => $totalCents,
                'status' => 'paid',
                'correlation_id' => $this->correlationId,
            ]);

            $artwork->update(['status' => 'sold']);

            Log::channel('audit')->info('Art order processed', [
                'order_id' => $order->id,
                'amount' => $totalCents,
                'correlation_id' => $this->correlationId,
            ]);

            return $order;
        }, 5);
    }

    /**
     * Get verified galleries for the current tenant.
     */
    public function getVerifiedGalleries(): Collection
    {
        return ArtGallery::where('is_verified', true)
            ->withCount('artworks')
            ->orderByDesc('rating')
            ->get();
    }
}
