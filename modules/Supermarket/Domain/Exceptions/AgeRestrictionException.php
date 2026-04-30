<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Exceptions;

use Exception;

final class AgeRestrictionException extends Exception
{
    private array $meta;

    public function __construct(string $message, array $meta = [])
    {
        $this->meta = $meta;
        parent::__construct($message, 403);
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
}
