<?php

declare(strict_types=1);

namespace Modules\Media\Domain\ValueObjects;

use ValueError;

final readonly class MediaMimeType
{
    private const ALLOWED_IMAGES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/avif', 'image/gif'];
    private const ALLOWED_VIDEOS = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
    private const ALLOWED_DOCUMENTS = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];

    public function __construct(
        public string $value,
    ) {
        $this->validate();
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    private function validate(): void
    {
        $allAllowed = [...self::ALLOWED_IMAGES, ...self::ALLOWED_VIDEOS, ...self::ALLOWED_DOCUMENTS];

        if (!in_array($this->value, $allAllowed, true)) {
            throw new ValueError("Invalid MIME type: {$this->value}");
        }
    }

    public function isImage(): bool
    {
        return in_array($this->value, self::ALLOWED_IMAGES, true);
    }

    public function isVideo(): bool
    {
        return in_array($this->value, self::ALLOWED_VIDEOS, true);
    }

    public function isDocument(): bool
    {
        return in_array($this->value, self::ALLOWED_DOCUMENTS, true);
    }

    public function getExtension(): string
    {
        return match (true) {
            str_contains($this->value, 'jpeg') => 'jpg',
            str_contains($this->value, 'png') => 'png',
            str_contains($this->value, 'webp') => 'webp',
            str_contains($this->value, 'avif') => 'avif',
            str_contains($this->value, 'gif') => 'gif',
            str_contains($this->value, 'mp4') => 'mp4',
            str_contains($this->value, 'webm') => 'webm',
            str_contains($this->value, 'quicktime') => 'mov',
            str_contains($this->value, 'msvideo') => 'avi',
            str_contains($this->value, 'pdf') => 'pdf',
            default => 'bin',
        };
    }
}
