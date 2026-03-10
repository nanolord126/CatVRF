<?php namespace App\Domains\Clinic\Events; use Illuminate\Foundation\Events\Dispatchable; use Illuminate\Queue\SerializesModels; class MedicalCardCreated { use Dispatchable, SerializesModels; public function __construct(public $medicalCard) {} }
class MedicalCardUpdated { use Dispatchable, SerializesModels; public function __construct(public $medicalCard) {} }
class MedicalCardDeleted { use Dispatchable, SerializesModels; public function __construct(public $medicalCard) {} }
