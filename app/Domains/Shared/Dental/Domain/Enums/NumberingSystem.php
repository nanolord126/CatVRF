<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Enums;

enum NumberingSystem: string
{
    case FDI = 'fdi';
    case UNIVERSAL = 'universal';

    public function getLabel(): string
    {
        return match ($this) {
            self::FDI => 'FDI (ISO 3950)',
            self::UNIVERSAL => 'Universal (US)',
        };
    }

    public function getAdultTeethRange(): array
    {
        return match ($this) {
            self::FDI => ['11', '12', '13', '14', '15', '16', '17', '18',
                         '21', '22', '23', '24', '25', '26', '27', '28',
                         '31', '32', '33', '34', '35', '36', '37', '38',
                         '41', '42', '43', '44', '45', '46', '47', '48'],
            self::UNIVERSAL => range(1, 32),
        };
    }

    public function getMilkTeethRange(): array
    {
        return match ($this) {
            self::FDI => ['51', '52', '53', '54', '55',
                         '61', '62', '63', '64', '65',
                         '71', '72', '73', '74', '75',
                         '81', '82', '83', '84', '85'],
            self::UNIVERSAL => ['A', 'B', 'C', 'D', 'E',
                               'F', 'G', 'H', 'I', 'J',
                               'K', 'L', 'M', 'N', 'O',
                               'P', 'Q', 'R', 'S', 'T'],
        };
    }
}
