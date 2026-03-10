<?php namespace App\Domains\Inventory\Events; use Illuminate\Foundation\Events\Dispatchable; use Illuminate\Queue\SerializesModels; class InventoryItemCreated { use Dispatchable, SerializesModels; public function __construct(public $inventoryItem) {} }
class InventoryItemUpdated { use Dispatchable, SerializesModels; public function __construct(public $inventoryItem) {} }
class InventoryItemDeleted { use Dispatchable, SerializesModels; public function __construct(public $inventoryItem) {} }
