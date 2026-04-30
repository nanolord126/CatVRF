<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum VaccineType: string
{
    case CORE_DHP = 'core_dhp';        // Distemper, Hepatitis, Parvovirus (dogs)
    case RABIES = 'rabies';            // Rabies (mandatory by law in Russia)
    case LEPTOSPIROSIS = 'leptospirosis'; // Leptospirosis (dogs, regional)
    case BORRELIA = 'borrelia';        // Lyme disease (dogs)
    case CORE_FPV = 'core_fpv';        // Panleukopenia (cats)
    case CORE_FCV_FHV1 = 'core_fcv_fhv1'; // Calicivirus + Herpes (cats)
    case FELV = 'felv';                // Feline leukemia (cats)
    case FIP = 'fip';                  // FIP (cats)
    case KENNEL_COUGH = 'kennel_cough'; // Bordetella (dogs)
    case OTHER = 'other';

    public function isDogVaccine(): bool
    {
        return in_array($this, [
            self::CORE_DHP,
            self::RABIES,
            self::LEPTOSPIROSIS,
            self::BORRELIA,
            self::KENNEL_COUGH,
        ]);
    }

    public function isCatVaccine(): bool
    {
        return in_array($this, [
            self::CORE_FPV,
            self::CORE_FCV_FHV1,
            self::FELV,
            self::FIP,
            self::RABIES,
        ]);
    }

    public function isCore(): bool
    {
        return in_array($this, [
            self::CORE_DHP,
            self::RABIES,
            self::CORE_FPV,
            self::CORE_FCV_FHV1,
        ]);
    }

    public function isMandatory(): bool
    {
        return $this === self::RABIES;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CORE_DHP => 'Комплексная (чумка, парвовирус, аденовирус)',
            self::RABIES => 'Бешенство',
            self::LEPTOSPIROSIS => 'Лептоспироз',
            self::BORRELIA => 'Боррелиоз (клещевой)',
            self::CORE_FPV => 'Панлейкопения',
            self::CORE_FCV_FHV1 => 'Калицивирус + Герпесвирус',
            self::FELV => 'Лейкоз кошек',
            self::FIP => 'Инфекционный перитонит',
            self::KENNEL_COUGH => 'Инфекционный трахеобронхит',
            self::OTHER => 'Другая',
        };
    }

    public function getStandardIntervalDays(): int
    {
        return match ($this) {
            self::RABIES => 365, // 1 year mandatory in Russia
            self::CORE_DHP, self::CORE_FPV, self::CORE_FCV_FHV1 => 1095, // 3 years after first booster
            self::LEPTOSPIROSIS, self::BORRELIA, self::KENNEL_COUGH => 365,
            self::FELV => 365,
            self::FIP => 365,
            self::OTHER => 365,
        };
    }
}
