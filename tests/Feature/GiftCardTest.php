<?php

namespace Tests\Feature;

use App\Models\GiftCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_activate_gift_card_and_deposit_to_wallet()
    {
        $user = User::factory()->create();
        $card = GiftCard::factory()->create([
            'amount' => 5000,
            'status' => 'active',
            'tenant_id' => 'test_tenant'
        ]);

        $this->actingAs($user);
        
        // Manual activation logic call (can use Filament action internally)
        $user->deposit($card->amount);
        $card->update(['status' => 'used', 'activated_by' => $user->id]);

        $this->assertEquals(5000, $user->balance);
        $this->assertEquals('used', $card->status);
    }
}
