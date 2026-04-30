<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Services;

use Modules\Recommendation\Application\DTOs\RecommendationRequestDTO;
use Modules\Recommendation\Domain\Enums\RecommendationSource;
use Modules\Recommendation\Domain\Repositories\RecommendationRepositoryInterface;

final readonly class RuleBasedFallbackService
{
    public function __construct(
        private RecommendationRepositoryInterface $repository,
    ) {}

    public function getFallbackRecommendations(RecommendationRequestDTO $request, string $correlationId): array
    {
        $fallbackItems = [];

        switch ($request->scenario->value) {
            case 'home_feed':
                $fallbackItems = $this->getHomeFeedFallback($request);
                break;
            case 'product_detail':
                $fallbackItems = $this->getProductDetailFallback($request);
                break;
            case 'search':
                $fallbackItems = $this->getSearchFallback($request);
                break;
            case 'seller_page':
                $fallbackItems = $this->getSellerPageFallback($request);
                break;
            case 'cart':
                $fallbackItems = $this->getCartFallback($request);
                break;
            default:
                $fallbackItems = $this->getGenericFallback($request);
        }

        foreach ($fallbackItems as &$item) {
            $item['source'] = RecommendationSource::FALLBACK->value;
            $item['confidence'] = 0.5;
            $item['reason'] = 'Fallback recommendations';
        }

        return array_slice($fallbackItems, 0, $request->limit);
    }

    private function getHomeFeedFallback(RecommendationRequestDTO $request): array
    {
        $items = [];

        $trending = $this->repository->getTrendingItems(
            $request->tenantId,
            limit: 30,
            vertical: $request->vertical
        );

        foreach ($trending as $idx => $trend) {
            $items[] = [
                'item_id' => $trend['item_id'],
                'seller_id' => $trend['seller_id'],
                'score' => $this->decayScore($trend['score'], $idx),
                'position' => $idx,
                'vertical' => $trend['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Trending item',
            ];
        }

        $popular = $this->repository->getTrendingItems(
            $request->tenantId,
            limit: 20,
            vertical: null
        );

        foreach ($popular as $idx => $pop) {
            $items[] = [
                'item_id' => $pop['item_id'],
                'seller_id' => $pop['seller_id'],
                'score' => $this->decayScore($pop['score'], $idx + 30) * 0.8,
                'position' => $idx + 30,
                'vertical' => $pop['vertical'] ?? 'unknown',
                'reason' => 'Popular across marketplace',
            ];
        }

        usort($items, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_values($items);
    }

    private function getProductDetailFallback(RecommendationRequestDTO $request): array
    {
        if (!$request->itemId) {
            return $this->getGenericFallback($request);
        }

        $items = [];

        $similar = $this->repository->getSimilarItems($request->tenantId, $request->itemId, 15);
        foreach ($similar as $idx => $item) {
            $items[] = [
                'item_id' => $item['item_id'],
                'seller_id' => $item['seller_id'],
                'score' => $this->decayScore($item['score'], $idx),
                'position' => $idx,
                'vertical' => $item['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Similar items',
            ];
        }

        $frequentlyBought = $this->repository->getFrequentlyBoughtTogether($request->tenantId, $request->itemId, 10);
        foreach ($frequentlyBought as $idx => $item) {
            $items[] = [
                'item_id' => $item['item_id'],
                'seller_id' => $item['seller_id'],
                'score' => $this->decayScore($item['score'], $idx + 15) * 0.9,
                'position' => $idx + 15,
                'vertical' => $item['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Frequently bought together',
            ];
        }

        $trending = $this->repository->getTrendingItems($request->tenantId, 10, $request->vertical);
        foreach ($trending as $idx => $item) {
            $items[] = [
                'item_id' => $item['item_id'],
                'seller_id' => $item['seller_id'],
                'score' => $this->decayScore($item['score'], $idx + 25) * 0.7,
                'position' => $idx + 25,
                'vertical' => $item['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Trending in category',
            ];
        }

        usort($items, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_values(array_slice($items, 0, 20));
    }

    private function getSearchFallback(RecommendationRequestDTO $request): array
    {
        $items = [];

        if ($request->vertical) {
            $verticalTrending = $this->repository->getTrendingItems(
                $request->tenantId,
                limit: 50,
                vertical: $request->vertical
            );

            foreach ($verticalTrending as $idx => $item) {
                $items[] = [
                    'item_id' => $item['item_id'],
                    'seller_id' => $item['seller_id'],
                    'score' => $this->decayScore($item['score'], $idx),
                    'position' => $idx,
                    'vertical' => $item['vertical'] ?? $request->vertical,
                    'reason' => 'Popular in this category',
                ];
            }
        } else {
            $globalTrending = $this->repository->getTrendingItems(
                $request->tenantId,
                limit: 50,
                vertical: null
            );

            foreach ($globalTrending as $idx => $item) {
                $items[] = [
                    'item_id' => $item['item_id'],
                    'seller_id' => $item['seller_id'],
                    'score' => $this->decayScore($item['score'], $idx),
                    'position' => $idx,
                    'vertical' => $item['vertical'] ?? 'unknown',
                    'reason' => 'Popular across marketplace',
                ];
            }
        }

        return array_values($items);
    }

    private function getSellerPageFallback(RecommendationRequestDTO $request): array
    {
        if (!$request->sellerId) {
            return $this->getGenericFallback($request);
        }

        $items = [];

        $trending = $this->repository->getTrendingItems(
            $request->tenantId,
            limit: 100,
            vertical: $request->vertical
        );

        $sellerItems = array_filter($trending, fn($item) => $item['seller_id'] === $request->sellerId);

        foreach ($sellerItems as $idx => $item) {
            $items[] = [
                'item_id' => $item['item_id'],
                'seller_id' => $item['seller_id'],
                'score' => $this->decayScore($item['score'], $idx),
                'position' => $idx,
                'vertical' => $item['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Popular from this seller',
            ];
        }

        if (count($items) < $request->limit) {
            $otherTrending = $this->repository->getTrendingItems(
                $request->tenantId,
                limit: $request->limit * 2,
                vertical: $request->vertical
            );

            $otherItems = array_filter($otherTrending, fn($item) => $item['seller_id'] !== $request->sellerId);

            foreach ($otherItems as $idx => $item) {
                $items[] = [
                    'item_id' => $item['item_id'],
                    'seller_id' => $item['seller_id'],
                    'score' => $this->decayScore($item['score'], $idx + count($items)) * 0.8,
                    'position' => $idx + count($items),
                    'vertical' => $item['vertical'] ?? $request->vertical ?? 'unknown',
                    'reason' => 'Similar items from other sellers',
                ];
            }
        }

        usort($items, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_values(array_slice($items, 0, $request->limit));
    }

    private function getCartFallback(RecommendationRequestDTO $request): array
    {
        $items = [];

        $trending = $this->repository->getTrendingItems(
            $request->tenantId,
            limit: 20,
            vertical: $request->vertical
        );

        foreach ($trending as $idx => $item) {
            $items[] = [
                'item_id' => $item['item_id'],
                'seller_id' => $item['seller_id'],
                'score' => $this->decayScore($item['score'], $idx),
                'position' => $idx,
                'vertical' => $item['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Popular items',
            ];
        }

        return array_values($items);
    }

    private function getGenericFallback(RecommendationRequestDTO $request): array
    {
        $items = [];

        $trending = $this->repository->getTrendingItems(
            $request->tenantId,
            limit: $request->limit * 2,
            vertical: $request->vertical
        );

        foreach ($trending as $idx => $item) {
            $items[] = [
                'item_id' => $item['item_id'],
                'seller_id' => $item['seller_id'],
                'score' => $this->decayScore($item['score'], $idx),
                'position' => $idx,
                'vertical' => $item['vertical'] ?? $request->vertical ?? 'unknown',
                'reason' => 'Popular item',
            ];
        }

        return array_values(array_slice($items, 0, $request->limit));
    }

    private function decayScore(float $score, int $position): float
    {
        $decayFactor = 1.0 / (1.0 + $position * 0.05);
        return $score * $decayFactor;
    }
}
