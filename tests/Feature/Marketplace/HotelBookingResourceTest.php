<?php
namespace Tests\Feature\Marketplace;
use App\Models\Marketplace\HotelBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class HotelBookingResourceTest extends TestCase
{use RefreshDatabase;private User$user;protected function setUp():void{parent::setUp();$this->user=User::factory()->create();$this->actingAs($this->user);}public function test_can_book_hotels():void{$this->post('/admin/hotel-bookings',['hotel_name'=>'Luxury Resort','check_in'=>now(),'check_out'=>now()->addDays(3)]);$this->assertDatabaseHas('hotel_bookings',['hotel_name'=>'Luxury Resort']);}}