<?php namespace App\Domains\Hotel\Events; use Illuminate\Foundation\Events\Dispatchable; use Illuminate\Queue\SerializesModels; class HotelBookingCreated { use Dispatchable, SerializesModels; public function __construct(public $hotelBooking) {} }
class HotelBookingUpdated { use Dispatchable, SerializesModels; public function __construct(public $hotelBooking) {} }
class HotelBookingDeleted { use Dispatchable, SerializesModels; public function __construct(public $hotelBooking) {} }
