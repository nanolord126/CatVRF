<?php declare(strict_types=1);

namespace App\Services\Art;


use Illuminate\Http\Request;
use App\Services\FraudControlService;
use App\Services\WalletService;
use App\Models\Art\Artwork;
use App\Models\Art\ArtOrder;
use App\Models\Art\ArtGallery;
use Illuminate\Database\Eloquent\Collection;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

final readonly class ArtService
{

    public function __construct(
        private readonly Request $request,
            private readonly FraudControlService $fraud,
            private readonly WalletService $wallet,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
    ) {}

        private function correlationId(): string
        {
            return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        }

        /**
         * Create a new artwork listing with high validation and safety.
         * @throws \Exception
         */
        public function registerArtwork(array $data): Artwork
        {
            $this->fraud->check((int) $this->guard->id(), 'art_registration', $this->request->ip());

            return $this->db->transaction(function () use ($data) {
                $artwork = Artwork::create(array_merge($data, [
                    'correlation_id' => $this->correlationId(),
                    'status' => 'pending', // Default to review for security
                ]));

                $this->logger->channel('audit')->info('Artwork registered', [
                    'id' => $artwork->id,
                    'uuid' => $artwork->uuid,
                    'correlation_id' => $this->correlationId(),
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
                throw new \DomainException('Artwork is not available for purchase.');
            }

            $this->fraud->check((int) $userId, 'art_purchase', $this->request->ip());

            return $this->db->transaction(function () use ($userId, $artwork, $type) {
                // Locking the artwork for the transaction duration
                $artwork->lockForUpdate()->increment('price_cents', 0);

                $totalCents = $artwork->price_cents;
                $commissionCents = (int)($totalCents * 0.14); // Standard Platform Commission 14%

                // Debit user wallet, credit gallery/artist wallet
                $this->wallet->debit($userId, $totalCents, \App\Domains\Wallet\Enums\BalanceTransactionType::WITHDRAWAL, $this->correlationId(), null, null, null);

                $this->wallet->credit($artwork->gallery_id ?? $artwork->user_id, $totalCents - $commissionCents, \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $this->correlationId(), null, null, ['artwork_id' => $artwork->id]);

                $artwork->update(['status' => 'sold', 'correlation_id' => $this->correlationId()]);

                return ArtOrder::create([
                    'user_id' => $userId,
                    'artwork_id' => $artwork->id,
                    'total_cents' => $totalCents,
                    'commission_cents' => $commissionCents,
                    'type' => $type,
                    'correlation_id' => $this->correlationId(),
                ]);
            });
        }
}
