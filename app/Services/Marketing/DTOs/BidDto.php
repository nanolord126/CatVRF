<?php declare(strict_types=1);

namespace App\Services\Marketing\DTOs;

final readonly class BidDto
{
    public function __construct(
        public string $id,
        public int $dspId,
        public int $campaignId,
        public int $adId,
        public float $priceKopecks,
        public string $currency,
        public array $ad,
        public string $impId,
    ) {}
}
