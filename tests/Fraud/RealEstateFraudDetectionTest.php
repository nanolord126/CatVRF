<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\RealEstate\Domain\Entities\Property;
use Modules\RealEstate\Domain\Entities\Listing;
use Modules\RealEstate\Domain\Enums\ListingStatus;
use Illuminate\Support\Facades\Cache;

final class RealEstateFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_listing_creation(): void
    {
        $userId = 1;

        // Simulate creating fake property listings
        for ($i = 0; $i < 15; $i++) {
            Listing::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'property_id' => Property::factory()->create()->id,
                'title' => 'Luxury Apartment',
                'price' => 50000000,
                'status' => ListingStatus::Active,
                'images' => [], // No images
                'description' => 'Short description',
            ]);
        }

        $this->fraudControl->checkListingFraud([
            'user_id' => $userId,
            'listing_count' => 15,
            'avg_image_count' => 0,
            'avg_description_length' => 20,
            'time_window_hours' => 24,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::FakeListing,
            FraudSeverity::High
        );
    }

    public function test_price_manipulation(): void
    {
        $listing = Listing::factory()->create(['price' => 10000000]);

        // Rapid price changes to manipulate market
        for ($i = 0; $i < 8; $i++) {
            $listing->update(['price' => $listing->price * 1.5]);
        }

        $this->fraudControl->checkPriceManipulation([
            'entity_type' => 'listing',
            'entity_id' => $listing->id,
            'price_change_count' => 8,
            'time_window_minutes' => 60,
            'change_percentage_total' => 2500,
        ]);

        $this->assertFraudAlertCreated(
            'listing',
            $listing->id,
            FraudType::PriceManipulation,
            FraudSeverity::High
        );
    }

    public function test_viewing_appointment_abuse(): void
    {
        $property = Property::factory()->create();
        $userId = 1;

        // User booking multiple viewings without intention to buy
        for ($i = 0; $i < 20; $i++) {
            $this->fraudControl->checkViewingAbuse([
                'user_id' => $userId,
                'property_id' => $property->id,
                'viewing_count' => 20,
                'purchase_count' => 0,
                'time_window_days' => 30,
            ]);
        }

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ResourceWaste,
            FraudSeverity::Medium
        );
    }

    public function test_fake_inquiry_spam(): void
    {
        $listing = Listing::factory()->create();
        $deviceFingerprint = 'fp_re_bot';

        // Bot sending fake inquiries
        for ($i = 0; $i < 50; $i++) {
            $this->fraudControl->checkInquirySpam([
                'listing_id' => $listing->id,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'message' => 'Interested in property',
                'contact_info' => 'fake@email.com',
            ]);
        }

        $this->assertFraudAlertCreated(
            'listing',
            $listing->id,
            FraudType::Spam,
            FraudSeverity::Medium
        );
    }

    public function test_deposit_fraud(): void
    {
        $userId = 1;
        $listing = Listing::factory()->create(['deposit_amount' => 100000]);

        // Simulate collecting deposits without intent to sell
        for ($i = 0; $i < 10; $i++) {
            $this->fraudControl->checkDepositFraud([
                'listing_id' => $listing->id,
                'user_id' => $userId,
                'deposit_collected' => 100000,
                'transaction_completed' => false,
                'refund_issued' => false,
                'days_elapsed' => 90,
            ]);
        }

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::FinancialFraud,
            FraudSeverity::Critical
        );
    }
}
