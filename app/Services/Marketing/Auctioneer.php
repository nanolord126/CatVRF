<?php declare(strict_types=1);

namespace App\Services\Marketing;

use App\Services\Marketing\DTOs\BidDto;
use App\Services\ML\BigDataAggregatorService;
use Illuminate\Support\Facades\Log;

/**
 * Auctioneer - Runs real-time auction for RTB bids
 * 
 * Supports First Price and Second Price auction strategies
 */
final readonly class Auctioneer
{
    public function __construct(
        private readonly BigDataAggregatorService $bigData,
    ) {}

    /**
     * Run auction and select winning bid
     * 
     * @param BidDto[] $bids
     * @param int $floorPriceKopecks
     * @return BidDto|null
     */
    public function run(array $bids, int $floorPriceKopecks): ?BidDto
    {
        if (empty($bids)) {
            return null;
        }

        // Filter bids below floor price
        $validBids = array_filter($bids, fn($bid) => $bid->priceKopecks >= $floorPriceKopecks);

        if (empty($validBids)) {
            return null;
        }

        // Sort by price descending
        usort($validBids, fn($a, $b) => $b->priceKopecks <=> $a->priceKopecks);

        $winningBid = $validBids[0];
        $auctionType = config('monetization.rtb.auction_type', 'second_price');

        // Adjust price based on auction type
        if ($auctionType === 'second_price' && count($validBids) > 1) {
            $secondPrice = $validBids[1]->priceKopecks;
            $winningBid = new BidDto(
                id: $winningBid->id,
                dspId: $winningBid->dspId,
                campaignId: $winningBid->campaignId,
                adId: $winningBid->adId,
                priceKopecks: max($floorPriceKopecks, $secondPrice),
                currency: $winningBid->currency,
                ad: $winningBid->ad,
                impId: $winningBid->impId,
            );
        }

        // Track auction win in analytics
        $this->bigData->insertMarketingEvent([
            'event_type' => 'rtb_auction_win',
            'dsp_id' => $winningBid->dspId,
            'campaign_id' => $winningBid->campaignId,
            'winning_price_kopecks' => $winningBid->priceKopecks,
            'floor_price_kopecks' => $floorPriceKopecks,
            'auction_type' => $auctionType,
            'total_bids' => count($bids),
            'valid_bids' => count($validBids),
            'created_at' => now()->toDateTimeString(),
        ]);

        Log::info('rtb.auction.completed', [
            'winning_bid' => $winningBid->id,
            'price' => $winningBid->priceKopecks,
            'auction_type' => $auctionType,
            'total_bids' => count($bids),
        ]);

        return $winningBid;
    }
}
