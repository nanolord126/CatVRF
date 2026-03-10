<?php namespace App\Domains\Sports\Events; use Illuminate\Foundation\Events\Dispatchable; use Illuminate\Queue\SerializesModels; class SportsMembershipCreated { use Dispatchable, SerializesModels; public function __construct(public $sportsMembership) {} }
class SportsMembershipUpdated { use Dispatchable, SerializesModels; public function __construct(public $sportsMembership) {} }
class SportsMembershipDeleted { use Dispatchable, SerializesModels; public function __construct(public $sportsMembership) {} }
