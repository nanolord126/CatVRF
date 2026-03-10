<?php namespace App\Domains\Delivery\Events; use Illuminate\Foundation\Events\Dispatchable; use Illuminate\Queue\SerializesModels; class DeliveryOrderCreated { use Dispatchable, SerializesModels; public function __construct(public $deliveryOrder) {} }
class DeliveryOrderUpdated { use Dispatchable, SerializesModels; public function __construct(public $deliveryOrder) {} }
class DeliveryOrderDeleted { use Dispatchable, SerializesModels; public function __construct(public $deliveryOrder) {} }
