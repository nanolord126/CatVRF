<?php namespace App\Domains\Food\Events; use Illuminate\Foundation\Events\Dispatchable; use Illuminate\Queue\SerializesModels; class FoodOrderCreated { use Dispatchable, SerializesModels; public function __construct(public $foodOrder) {} }
class FoodOrderUpdated { use Dispatchable, SerializesModels; public function __construct(public $foodOrder) {} }
class FoodOrderDeleted { use Dispatchable, SerializesModels; public function __construct(public $foodOrder) {} }
