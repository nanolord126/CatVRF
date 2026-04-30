<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

enum CertificationType: string
{
    case INTERNAL = 'internal';
    case EXTERNAL = 'external';

    public function getLabel(): string
    {
        return match ($this) {
            self::INTERNAL => 'Internal CatCRM Certification',
            self::EXTERNAL => 'External Certification',
        };
    }
}
