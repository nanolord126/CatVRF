<?php namespace App\Domains\Taxi\Events; use Illuminate\Foundation\Events\Dispatchable; use Illuminate\Queue\SerializesModels; class TaxiRideCreated { use Dispatchable, SerializesModels; public function __construct(public $taxiRide) {} }
class TaxiRideUpdated { use Dispatchable, SerializesModels; public function __construct(public $taxiRide) {} }
class TaxiRideDeleted { use Dispatchable, SerializesModels; public function __construct(public $taxiRide) {} }
