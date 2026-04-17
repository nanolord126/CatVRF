<?php declare(strict_types=1);

namespace Tests\Security;

use App\Domains\RealEstate\Models\Property;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RealEstateSpamTest extends SecurityTestCase
{
    use RefreshDatabase;

    private User $user;
    private User $spammer;
    private Tenant $tenant;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->spammer = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
        ]);
    }

    public function test_bulk_property_creation_blocked(): void
    {
        $responses = [];
        
        for ($i = 0; $i < 50; $i++) {
            $propertyData = [
                'tenant_id' => $this->tenant->id,
                'type' => 'apartment',
                'area_sqm' => 75.5,
                'price' => 10000000.00,
                'address' => "Test Address {$i}",
                'lat' => 55.7558,
                'lon' => 37.6173,
            ];

            $responses[] = $this->actingAs($this->spammer)
                ->postJson('/api/real-estate/properties', $propertyData);
        }

        $blockedCount = collect($responses)->filter(fn($r) => in_array($r->status(), [429, 403, 422]))->count();
        $this->assertGreaterThan(10, $blockedCount, 'Bulk property creation should be rate-limited');
    }

    public function test_duplicate_inquiry_spam_blocked(): void
    {
        $inquiryData = [
            'property_id' => $this->property->id,
            'message' => 'I am interested in this property',
            'contact_phone' => '+79001234567',
        ];

        $responses = [];
        for ($i = 0; $i < 20; $i++) {
            $responses[] = $this->actingAs($this->spammer)
                ->postJson('/api/real-estate/inquiries', $inquiryData);
        }

        $blockedCount = collect($responses)->filter(fn($r) => in_array($r->status(), [429, 403, 409]))->count();
        $this->assertGreaterThan(5, $blockedCount, 'Duplicate inquiries should be blocked');
    }

    public function test_spam_keywords_in_property_description_blocked(): void
    {
        $spamProperties = [
            [
                'tenant_id' => $this->tenant->id,
                'type' => 'apartment',
                'area_sqm' => 75.5,
                'price' => 10000000.00,
                'description' => 'CLICK HERE NOW FREE MONEY MAKE MILLIONS BITCOIN CRYPTO',
                'address' => 'Test Address 1',
            ],
            [
                'tenant_id' => $this->tenant->id,
                'type' => 'apartment',
                'area_sqm' => 75.5,
                'price' => 10000000.00,
                'description' => 'WINNER YOU HAVE WON PRIZE CLICK LINK',
                'address' => 'Test Address 2',
            ],
        ];

        foreach ($spamProperties as $spamProperty) {
            $response = $this->actingAs($this->spammer)
                ->postJson('/api/real-estate/properties', $spamProperty);

            if ($response->status() === 200) {
                $this->assertHasSpamScore($response);
                $this->assertGreaterThan(0.8, $response->json('spam_score'), 'Spam keywords should trigger high spam score');
            } else {
                $this->assertContains($response->status(), [422, 403], 'Spam content should be blocked');
            }
        }
    }

    public function test_contact_form_spam_blocked(): void
    {
        $contactData = [
            'property_id' => $this->property->id,
            'name' => 'Spammer',
            'email' => 'spam@example.com',
            'message' => 'Spam message repeated many times',
        ];

        $responses = [];
        for ($i = 0; $i < 30; $i++) {
            $contactData['message'] = "Spam message {$i}";
            $responses[] = $this->actingAs($this->spammer)
                ->postJson('/api/real-estate/contact', $contactData);
        }

        $blockedCount = collect($responses)->filter(fn($r) => in_array($r->status(), [429, 403]))->count();
        $this->assertGreaterThan(15, $blockedCount, 'Contact form spam should be rate-limited');
    }

    public function test_review_spam_blocked(): void
    {
        $reviewData = [
            'property_id' => $this->property->id,
            'rating' => 5,
            'comment' => 'Great property!',
        ];

        $responses = [];
        for ($i = 0; $i < 15; $i++) {
            $responses[] = $this->actingAs($this->spammer)
                ->postJson('/api/real-estate/reviews', $reviewData);
        }

        $duplicateCount = collect($responses)->filter(fn($r) => $r->status() === 409)->count();
        $this->assertGreaterThan(0, $duplicateCount, 'Duplicate reviews should be blocked');
    }

    public function test_fake_agent_spam_blocked(): void
    {
        $fakeAgentData = [
            'tenant_id' => $this->tenant->id,
            'name' => 'Fake Agent',
            'phone' => '+79009999999',
            'email' => 'fake@example.com',
            'license_number' => 'FAKE123',
        ];

        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $fakeAgentData['email'] = "fake{$i}@example.com";
            $responses[] = $this->actingAs($this->spammer)
                ->postJson('/api/real-estate/agents', $fakeAgentData);
        }

        $blockedCount = collect($responses)->filter(fn($r) => in_array($r->status(), [429, 403]))->count();
        $this->assertGreaterThan(3, $blockedCount, 'Fake agent creation should be rate-limited');
    }

    public function test_mass_viewing_request_spam_blocked(): void
    {
        $viewingData = [
            'property_id' => $this->property->id,
            'scheduled_at' => now()->addDay(),
            'contact_phone' => '+79001234567',
        ];

        $responses = [];
        for ($i = 0; $i < 25; $i++) {
            $viewingData['scheduled_at'] = now()->addDays($i);
            $responses[] = $this->actingAs($this->spammer)
                ->postJson('/api/real-estate/viewings', $viewingData);
        }

        $blockedCount = collect($responses)->filter(fn($r) => in_array($r->status(), [429, 403]))->count();
        $this->assertGreaterThan(10, $blockedCount, 'Mass viewing requests should be blocked');
    }

    public function test_promotional_spam_in_messages_blocked(): void
    {
        $spamMessages = [
            'BUY NOW LIMITED OFFER CLICK HERE',
            'MAKE MONEY FAST REAL ESTATE SECRET',
            'FREE APARTMENT NO CREDIT CHECK SCAM',
            'BITCOIN INVESTMENT OPPORTUNITY',
            'WIN FREE HOUSE LOTTERY',
        ];

        foreach ($spamMessages as $spamMessage) {
            $messageData = [
                'property_id' => $this->property->id,
                'message' => $spamMessage,
            ];

            $response = $this->actingAs($this->spammer)
                ->postJson('/api/real-estate/messages', $messageData);

            if ($response->status() === 200) {
                $this->assertHasSpamScore($response);
                $this->assertGreaterThan(0.7, $response->json('spam_score'), 'Promotional spam should trigger spam detection');
            } else {
                $this->assertContains($response->status(), [422, 403], 'Promotional spam should be blocked');
            }
        }
    }

    public function test_phishing_link_detection(): void
    {
        $phishingMessages = [
            'Click here for your property: http://fake-phishing-site.com/login',
            'Verify your account at http://scam-site.xyz/verify',
            'Update payment info at http://evil-net.org/payment',
        ];

        foreach ($phishingMessages as $phishingMessage) {
            $messageData = [
                'property_id' => $this->property->id,
                'message' => $phishingMessage,
            ];

            $response = $this->actingAs($this->spammer)
                ->postJson('/api/real-estate/messages', $messageData);

            if ($response->status() === 200) {
                $this->assertHasSpamScore($response);
                $this->assertGreaterThan(0.9, $response->json('spam_score'), 'Phishing links should trigger very high spam score');
            } else {
                $this->assertContains($response->status(), [422, 403], 'Phishing links should be blocked');
            }
        }
    }

    public function test_repetitive_pattern_spam_blocked(): void
    {
        $repetitiveMessage = str_repeat('Great property contact me now ', 20);

        $messageData = [
            'property_id' => $this->property->id,
            'message' => $repetitiveMessage,
        ];

        $response = $this->actingAs($this->spammer)
            ->postJson('/api/real-estate/messages', $messageData);

        if ($response->status() === 200) {
            $this->assertHasSpamScore($response);
            $this->assertGreaterThan(0.6, $response->json('spam_score'), 'Repetitive patterns should trigger spam detection');
        } else {
            $this->assertContains($response->status(), [422, 403], 'Repetitive spam should be blocked');
        }
    }

    public function test_multiple_account_spam_blocked(): void
    {
        $accounts = [];
        for ($i = 0; $i < 5; $i++) {
            $accounts[] = User::factory()->create([
                'tenant_id' => $this->tenant->id,
                'email' => "spammer{$i}@example.com",
            ]);
        }

        $propertyData = [
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
            'address' => 'Test Address',
        ];

        $responses = [];
        foreach ($accounts as $account) {
            $responses[] = $this->actingAs($account)
                ->postJson('/api/real-estate/properties', $propertyData);
        }

        $sameIpCount = collect($responses)->filter(fn($r) => in_array($r->status(), [429, 403]))->count();
        $this->assertGreaterThan(0, $sameIpCount, 'Multiple accounts from same IP should be rate-limited');
    }

    protected function assertHasSpamScore($response): void
    {
        $this->assertArrayHasKey('spam_score', $response->json(), 'Response should include spam_score');
    }
}
