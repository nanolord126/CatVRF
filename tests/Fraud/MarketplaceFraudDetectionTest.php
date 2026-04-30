<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Marketplace\Domain\Entities\Listing;
use Modules\Marketplace\Domain\Entities\Order;
use Modules\Marketplace\Domain\Enums\ListingStatus;
use Modules\Marketplace\Domain\Enums\OrderStatus;
use Illuminate\Support\Facades\Cache;

final class MarketplaceFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_listing_creation(): void
    {
        $sellerId = 1;

        // Create multiple fake listings
        for ($i = 0; $i < 25; $i++) {
            Listing::create([
                'seller_id' => $sellerId,
                'tenant_id' => 1,
                'title' => 'Product Title',
                'price' => 1000,
                'description' => 'Short description',
                'images' => [],
                'status' => ListingStatus::Active,
            ]);
        }

        $this->fraudControl->checkListingFraud([
            'seller_id' => $sellerId,
            'listing_count' => 25,
            'avg_image_count' => 0,
            'avg_description_length' => 20,
            'time_window_hours' => 24,
        ]);

        $this->assertFraudAlertCreated(
            'seller',
            $sellerId,
            FraudType::FakeListing,
            FraudSeverity::High
        );
    }

    public function test_shill_bidding(): void
    {
        $listingId = 1;
        $sellerId = 1;

        // Simulate seller bidding on own items
        for ($i = 0; $i < 15; $i++) {
            $this->fraudControl->checkShillBidding([
                'listing_id' => $listingId,
                'bidder_id' => $sellerId + $i,
                'seller_id' => $sellerId,
                'ip_address' => '192.168.1.80',
                'device_fingerprint' => 'fp_shill_bot',
                'bid_amount' => 1000 * ($i + 1),
            ]);
        }

        $this->assertFraudAlertCreated(
            'listing',
            $listingId,
            FraudType::AuctionFraud,
            FraudSeverity::Critical
        );
    }

    public function test_non_delivery_fraud(): void
    {
        $sellerId = 1;

        // Seller taking orders but not delivering
        for ($i = 0; $i < 20; $i++) {
            $order = Order::create([
                'seller_id' => $sellerId,
                'buyer_id' => $i + 1,
                'tenant_id' => 1,
                'amount' => 5000,
                'status' => OrderStatus::Paid,
            ]);

            // Never ships
            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now()->addDays(30), // After maximum shipping time
            ]);
        }

        $this->fraudControl->checkNonDelivery([
            'seller_id' => $sellerId,
            'non_delivery_count' => 20,
            'total_amount' => 100000,
            'time_window_days' => 30,
        ]);

        $this->assertFraudAlertCreated(
            'seller',
            $sellerId,
            FraudType::FinancialFraud,
            FraudSeverity::Critical
        );
    }

    public function test_fake_review_manipulation(): void
    {
        $sellerId = 1;
        $deviceFingerprint = 'fp_marketplace_bot';

        for ($i = 0; $i < 40; $i++) {
            $this->fraudControl->checkReviewManipulation([
                'entity_type' => 'seller',
                'entity_id' => $sellerId,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'rating' => 5,
                'comment' => 'Great seller!',
            ]);
        }

        $this->assertFraudAlertCreated(
            'seller',
            $sellerId,
            FraudType::FakeReviews,
            FraudSeverity::High
        );
    }

    public function test_price_manipulation(): void
    {
        $listing = Listing::factory()->create(['price' => 1000]);

        // Rapid price changes to manipulate market
        for ($i = 0; $i < 10; $i++) {
            $listing->update(['price' => $listing->price * 2]);
        }

        $this->fraudControl->checkPriceManipulation([
            'entity_type' => 'listing',
            'entity_id' => $listing->id,
            'price_change_count' => 10,
            'time_window_minutes' => 30,
            'change_percentage_total' => 1000,
        ]);

        $this->assertFraudAlertCreated(
            'listing',
            $listing->id,
            FraudType::PriceManipulation,
            FraudSeverity::High
        );
    }
}
