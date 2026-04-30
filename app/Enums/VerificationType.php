<?php

declare(strict_types=1);

namespace App\Enums;

enum VerificationType: string
{
    public function label(): string
    {
        return match ($this) {
            self::FioPhoto => 'Верификация ФИО + фото',
            self::Inn => 'Проверка ИНН',
            self::Document => 'Проверка документов',
            self::Manual => 'Ручная проверка',
            self::Passkey => 'Верификация Passkey',
        };
    }

    public function isAutomated(): bool
    {
        return match ($this) {
            self::FioPhoto, self::Inn, self::Document, self::Passkey => true,
            self::Manual => false,
        };
    }

    public function requiresAi(): bool
    {
        return match ($this) {
            self::FioPhoto, self::Document => true,
            self::Inn, self::Manual, self::Passkey => false,
        };
    }
    case FioPhoto = 'fio_photo';       // FIO + photo verification with liveness
    case Inn = 'inn';                  // INN validation via DaData
    case Document = 'document';        // Document OCR and validation
    case Manual = 'manual';            // Manual moderator review
    case Passkey = 'passkey';          // Passkey/WebAuthn verification
}
