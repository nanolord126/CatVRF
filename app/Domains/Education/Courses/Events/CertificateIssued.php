<?php

declare(strict_types=1);


namespace App\Domains\Education\Courses\Events;

use App\Domains\Education\Courses\Models\Certificate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * CertificateIssued
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CertificateIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Certificate $certificate,
        public readonly string $correlationId = '',
    ) {}
}
