<?php declare(strict_types=1);

namespace App\Services\Marketing;

use App\Services\Marketing\DTOs\BidDto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BidCollector - Collects bids from DSPs (Demand Side Platforms)
 * 
 * Uses Horizon jobs for async bid collection to external DSPs
 */
final readonly class BidCollector
{
    /**
     * Collect bids from all configured DSPs
     * 
     * @param array $bidRequest OpenRTB BidRequest as array
     * @param int $timeoutMs Timeout in milliseconds
     * @return BidDto[]
     */
    public function collect(array $bidRequest, int $timeoutMs): array
    {
        $dsps = config('monetization.rtb.dsps', []);
        $bids = [];
        $timeoutSeconds = $timeoutMs / 1000;

        foreach ($dsps as $dspId => $dspConfig) {
            try {
                // Internal DSP (self-serve)
                if ($dspId === 0) {
                    $internalBids = $this->collectInternalBids($bidRequest);
                    $bids = array_merge($bids, $internalBids);
                    continue;
                }

                // External DSPs (async via HTTP)
                if (isset($dspConfig['endpoint']) && $dspConfig['endpoint'] !== null) {
                    $externalBids = $this->collectExternalBids(
                        $dspId,
                        $dspConfig['endpoint'],
                        $bidRequest,
                        $timeoutSeconds
                    );
                    $bids = array_merge($bids, $externalBids);
                }
            } catch (\Throwable $e) {
                Log::warning('rtb.bid_collection.failed', [
                    'dsp_id' => $dspId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $bids;
    }

    /**
     * Collect bids from internal DSP (self-serve campaigns)
     */
    private function collectInternalBids(array $bidRequest): array
    {
        // This would query the ads table for matching campaigns
        // For now, return empty array - implementation would match ads to bidRequest
        return [];
    }

    /**
     * Collect bids from external DSP via HTTP
     */
    private function collectExternalBids(int $dspId, string $endpoint, array $bidRequest, float $timeout): array
    {
        $response = Http::timeout($timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-openrtb-version' => '2.6',
            ])
            ->post($endpoint, $bidRequest);

        if (!$response->successful()) {
            Log::warning('rtb.external_dsp.error', [
                'dsp_id' => $dspId,
                'status' => $response->status(),
            ]);
            return [];
        }

        $bidResponse = $response->json();
        return $this->parseBidResponse($dspId, $bidResponse);
    }

    /**
     * Parse OpenRTB BidResponse into BidDto array
     */
    private function parseBidResponse(int $dspId, array $bidResponse): array
    {
        $bids = [];

        if (!isset($bidResponse['seatbid'])) {
            return $bids;
        }

        foreach ($bidResponse['seatbid'] as $seatBid) {
            if (!isset($seatBid['bid'])) {
                continue;
            }

            foreach ($seatBid['bid'] as $bid) {
                $bids[] = new BidDto(
                    id: $bid['id'] ?? uniqid(),
                    dspId: $dspId,
                    campaignId: $bid['crid'] ?? 0,
                    adId: $bid['crid'] ?? 0,
                    priceKopecks: $this->convertPriceToKopecks($bid['price'] ?? 0),
                    currency: 'RUB',
                    ad: [
                        'adm' => $bid['adm'] ?? '',
                        'nurl' => $bid['nurl'] ?? '',
                    ],
                    impId: $bid['impid'] ?? '',
                );
            }
        }

        return $bids;
    }

    /**
     * Convert price from CPM (micro-cents per mille) to kopecks
     */
    private function convertPriceToKopecks(float $price): float
    {
        // OpenRTB price is in micro-cents per mille
        // Convert to kopecks: (price / 1000000) * 100
        return ($price / 1000000) * 100;
    }
}
