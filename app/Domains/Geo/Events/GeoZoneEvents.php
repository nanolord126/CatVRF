<?php namespace App\Domains\Geo\Events; use Illuminate\Foundation\Events\Dispatchable; use Illuminate\Queue\SerializesModels; class GeoZoneCreated { use Dispatchable, SerializesModels; public function __construct(public $geoZone) {} }
class GeoZoneUpdated { use Dispatchable, SerializesModels; public function __construct(public $geoZone) {} }
class GeoZoneDeleted { use Dispatchable, SerializesModels; public function __construct(public $geoZone) {} }
