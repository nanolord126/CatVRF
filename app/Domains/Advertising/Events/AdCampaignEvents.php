<?php namespace App\Domains\Advertising\Events; use Illuminate\Foundation\Events\Dispatchable; use Illuminate\Queue\SerializesModels; class AdCampaignCreated { use Dispatchable, SerializesModels; public function __construct(public $adCampaign) {} }
class AdCampaignUpdated { use Dispatchable, SerializesModels; public function __construct(public $adCampaign) {} }
class AdCampaignDeleted { use Dispatchable, SerializesModels; public function __construct(public $adCampaign) {} }
