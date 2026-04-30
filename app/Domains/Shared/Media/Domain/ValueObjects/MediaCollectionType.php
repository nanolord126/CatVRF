<?php

declare(strict_types=1);

namespace Modules\Media\Domain\ValueObjects;

enum MediaCollectionType: string
{
    case AVATAR = 'avatar';
    case IMAGES = 'images';
    case DOCUMENTS = 'documents';
    case MEDICAL = 'medical';
    case BEFORE_AFTER = 'before_after';
    case PORTFOLIO = 'portfolio';
    case PRODUCTS = 'products';
    case SERVICES = 'services';
    case VIDEO_RECORDING = 'video_recording';
    case VIDEO_THUMBNAIL = 'video_thumbnail';

    public function isImageCollection(): bool
    {
        return in_array($this, [self::AVATAR, self::IMAGES, self::MEDICAL, self::BEFORE_AFTER, self::PORTFOLIO, self::PRODUCTS, self::SERVICES], true);
    }

    public function isVideoCollection(): bool
    {
        return in_array($this, [self::VIDEO_RECORDING, self::VIDEO_THUMBNAIL], true);
    }

    public function isDocumentCollection(): bool
    {
        return $this === self::DOCUMENTS;
    }
}
