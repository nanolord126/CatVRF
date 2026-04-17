<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use Tests\TestCase;
use App\Domains\RealEstate\Models\PropertyViewing;
use App\Domains\RealEstate\Models\Property;
use App\Models\Domains\RealEstate\RealEstateAgent;
use App\Models\User;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class PropertyViewingModelTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Property $property;
    private User $user;
    private RealEstateAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->property = Property::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create();
        $this->agent = RealEstateAgent::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_property_viewing_has_fillable_fields(): void
    {
        $viewing = PropertyViewing::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
            'agent_id' => $this->agent->id,
            'scheduled_at' => Carbon::now()->addDays(2),
            'status' => 'pending',
            'is_b2b' => false,
            'correlation_id' => \Illuminate\Support\Str::uuid(),
        ]);

        $this->assertDatabaseHas('property_viewings', [
            'id' => $viewing->id,
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_scope_active_filters_correct_statuses(): void
    {
        PropertyViewing::factory()->create(['status' => 'pending', 'tenant_id' => $this->tenant->id]);
        PropertyViewing::factory()->create(['status' => 'held', 'tenant_id' => $this->tenant->id]);
        PropertyViewing::factory()->create(['status' => 'confirmed', 'tenant_id' => $this->tenant->id]);
        PropertyViewing::factory()->create(['status' => 'completed', 'tenant_id' => $this->tenant->id]);
        PropertyViewing::factory()->create(['status' => 'cancelled', 'tenant_id' => $this->tenant->id]);

        $activeViewings = PropertyViewing::active()->get();

        $this->assertCount(3, $activeViewings);
        $activeViewings->each(fn ($viewing) => $this->assertContains($viewing->status, ['pending', 'held', 'confirmed']));
    }

    public function test_scope_held_filters_held_and_not_expired(): void
    {
        PropertyViewing::factory()->held()->create([
            'tenant_id' => $this->tenant->id,
            'hold_expires_at' => Carbon::now()->addMinutes(10),
        ]);
        PropertyViewing::factory()->held()->create([
            'tenant_id' => $this->tenant->id,
            'hold_expires_at' => Carbon::now()->subMinutes(10),
        ]);
        PropertyViewing::factory()->create(['status' => 'pending', 'tenant_id' => $this->tenant->id]);

        $heldViewings = PropertyViewing::held()->get();

        $this->assertCount(1, $heldViewings);
        $this->assertTrue($heldViewings->first()->hold_expires_at->isFuture());
    }

    public function test_scope_expired_filters_expired_holds(): void
    {
        PropertyViewing::factory()->held()->create([
            'tenant_id' => $this->tenant->id,
            'hold_expires_at' => Carbon::now()->addMinutes(10),
        ]);
        PropertyViewing::factory()->held()->create([
            'tenant_id' => $this->tenant->id,
            'hold_expires_at' => Carbon::now()->subMinutes(10),
        ]);

        $expiredViewings = PropertyViewing::expired()->get();

        $this->assertCount(1, $expiredViewings);
        $this->assertTrue($expiredViewings->first()->hold_expires_at->isPast());
    }

    public function test_scope_b2c_filters_b2b_false(): void
    {
        PropertyViewing::factory()->b2c()->create(['tenant_id' => $this->tenant->id]);
        PropertyViewing::factory()->b2c()->create(['tenant_id' => $this->tenant->id]);
        PropertyViewing::factory()->b2b()->create(['tenant_id' => $this->tenant->id]);

        $b2cViewings = PropertyViewing::b2c()->get();

        $this->assertCount(2, $b2cViewings);
        $b2cViewings->each(fn ($viewing) => $this->assertFalse($viewing->is_b2b));
    }

    public function test_scope_b2b_filters_b2b_true(): void
    {
        PropertyViewing::factory()->b2c()->create(['tenant_id' => $this->tenant->id]);
        PropertyViewing::factory()->b2b()->create(['tenant_id' => $this->tenant->id]);
        PropertyViewing::factory()->b2b()->create(['tenant_id' => $this->tenant->id]);

        $b2bViewings = PropertyViewing::b2b()->get();

        $this->assertCount(2, $b2bViewings);
        $b2bViewings->each(fn ($viewing) => $this->assertTrue($viewing->is_b2b));
    }

    public function test_scope_for_property_filters_by_property_id(): void
    {
        $property2 = Property::factory()->create(['tenant_id' => $this->tenant->id]);

        PropertyViewing::factory()->count(3)->create(['property_id' => $this->property->id, 'tenant_id' => $this->tenant->id]);
        PropertyViewing::factory()->count(2)->create(['property_id' => $property2->id, 'tenant_id' => $this->tenant->id]);

        $property1Viewings = PropertyViewing::forProperty($this->property->id)->get();

        $this->assertCount(3, $property1Viewings);
        $property1Viewings->each(fn ($viewing) => $this->assertEquals($this->property->id, $viewing->property_id));
    }

    public function test_scope_for_user_filters_by_user_id(): void
    {
        $user2 = User::factory()->create();

        PropertyViewing::factory()->count(3)->create(['user_id' => $this->user->id, 'tenant_id' => $this->tenant->id]);
        PropertyViewing::factory()->count(2)->create(['user_id' => $user2->id, 'tenant_id' => $this->tenant->id]);

        $user1Viewings = PropertyViewing::forUser($this->user->id)->get();

        $this->assertCount(3, $user1Viewings);
        $user1Viewings->each(fn ($viewing) => $this->assertEquals($this->user->id, $viewing->user_id));
    }

    public function test_scope_scheduled_between_filters_by_date_range(): void
    {
        $startDate = Carbon::now()->addDays(5);
        $endDate = Carbon::now()->addDays(10);

        PropertyViewing::factory()->create([
            'scheduled_at' => Carbon::now()->addDays(3),
            'tenant_id' => $this->tenant->id,
        ]);
        PropertyViewing::factory()->create([
            'scheduled_at' => Carbon::now()->addDays(7),
            'tenant_id' => $this->tenant->id,
        ]);
        PropertyViewing::factory()->create([
            'scheduled_at' => Carbon::now()->addDays(8),
            'tenant_id' => $this->tenant->id,
        ]);
        PropertyViewing::factory()->create([
            'scheduled_at' => Carbon::now()->addDays(15),
            'tenant_id' => $this->tenant->id,
        ]);

        $rangeViewings = PropertyViewing::scheduledBetween($startDate, $endDate)->get();

        $this->assertCount(2, $rangeViewings);
    }

    public function test_is_expired_returns_correct_value(): void
    {
        $expiredViewing = PropertyViewing::factory()->held()->create([
            'tenant_id' => $this->tenant->id,
            'hold_expires_at' => Carbon::now()->subMinutes(10),
        ]);

        $activeViewing = PropertyViewing::factory()->held()->create([
            'tenant_id' => $this->tenant->id,
            'hold_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $this->assertTrue($expiredViewing->isExpired());
        $this->assertFalse($activeViewing->isExpired());
    }

    public function test_is_confirmed_returns_correct_value(): void
    {
        $confirmedViewing = PropertyViewing::factory()->create([
            'status' => 'confirmed',
            'tenant_id' => $this->tenant->id,
        ]);

        $pendingViewing = PropertyViewing::factory()->create([
            'status' => 'pending',
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertTrue($confirmedViewing->isConfirmed());
        $this->assertFalse($pendingViewing->isConfirmed());
    }

    public function test_is_completed_returns_correct_value(): void
    {
        $completedViewing = PropertyViewing::factory()->create([
            'status' => 'completed',
            'tenant_id' => $this->tenant->id,
        ]);

        $pendingViewing = PropertyViewing::factory()->create([
            'status' => 'pending',
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertTrue($completedViewing->isCompleted());
        $this->assertFalse($pendingViewing->isCompleted());
    }

    public function test_is_cancelled_returns_correct_value(): void
    {
        $cancelledViewing = PropertyViewing::factory()->create([
            'status' => 'cancelled',
            'tenant_id' => $this->tenant->id,
        ]);

        $pendingViewing = PropertyViewing::factory()->create([
            'status' => 'pending',
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertTrue($cancelledViewing->isCancelled());
        $this->assertFalse($pendingViewing->isCancelled());
    }

    public function test_property_relationship(): void
    {
        $viewing = PropertyViewing::factory()->create([
            'property_id' => $this->property->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertInstanceOf(Property::class, $viewing->property);
        $this->assertEquals($this->property->id, $viewing->property->id);
    }

    public function test_user_relationship(): void
    {
        $viewing = PropertyViewing::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertInstanceOf(User::class, $viewing->user);
        $this->assertEquals($this->user->id, $viewing->user->id);
    }

    public function test_agent_relationship(): void
    {
        $viewing = PropertyViewing::factory()->create([
            'agent_id' => $this->agent->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertInstanceOf(RealEstateAgent::class, $viewing->agent);
        $this->assertEquals($this->agent->id, $viewing->agent->id);
    }

    public function test_uuid_is_generated_on_creation(): void
    {
        $viewing = PropertyViewing::factory()->create([
            'uuid' => null,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertNotNull($viewing->uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $viewing->uuid);
    }

    public function test_correlation_id_is_generated_on_creation(): void
    {
        $viewing = PropertyViewing::factory()->create([
            'correlation_id' => null,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertNotNull($viewing->correlation_id);
    }

    public function test_casts_work_correctly(): void
    {
        $viewing = PropertyViewing::factory()->create([
            'tenant_id' => $this->tenant->id,
            'metadata' => ['key' => 'value'],
            'tags' => ['tag1', 'tag2'],
        ]);

        $this->assertIsArray($viewing->metadata);
        $this->assertIsArray($viewing->tags);
        $this->assertEquals(['key' => 'value'], $viewing->metadata);
        $this->assertEquals(['tag1', 'tag2'], $viewing->tags);
    }
}
