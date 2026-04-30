<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum MedicalDocumentType: string
{
    case LAB_RESULT = 'lab_result';
    case XRAY = 'xray';
    case ULTRASOUND = 'ultrasound';
    case ECG = 'ecg';
    case MRI = 'mri';
    case CT_SCAN = 'ct_scan';
    case PASSPORT = 'passport';
    case VACCINATION_CERTIFICATE = 'vaccination_certificate';
    case PRESCRIPTION = 'prescription';
    case REFERRAL = 'referral';
    case DISCHARGE_SUMMARY = 'discharge_summary';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::LAB_RESULT => 'Результат анализов',
            self::XRAY => 'Рентген',
            self::ULTRASOUND => 'УЗИ',
            self::ECG => 'ЭКГ',
            self::MRI => 'МРТ',
            self::CT_SCAN => 'КТ',
            self::PASSPORT => 'Паспорт животного',
            self::VACCINATION_CERTIFICATE => 'Сертификат вакцинации',
            self::PRESCRIPTION => 'Рецепт',
            self::REFERRAL => 'Направление',
            self::DISCHARGE_SUMMARY => 'Выписной эпикриз',
            self::OTHER => 'Другое',
        };
    }

    public function isImaging(): bool
    {
        return in_array($this, [
            self::XRAY,
            self::ULTRASOUND,
            self::ECG,
            self::MRI,
            self::CT_SCAN,
        ]);
    }

    public function isCertificate(): bool
    {
        return in_array($this, [
            self::PASSPORT,
            self::VACCINATION_CERTIFICATE,
        ]);
    }

    public function isDiagnostic(): bool
    {
        return in_array($this, [
            self::LAB_RESULT,
            self::XRAY,
            self::ULTRASOUND,
            self::ECG,
            self::MRI,
            self::CT_SCAN,
        ]);
    }
}
