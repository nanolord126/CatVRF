<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Exceptions;

use Exception;

final class ReturnPolicyViolationException extends Exception
{
    private array $violations;

    public function __construct(array $violations)
    {
        $this->violations = $violations;
        $message = $this->formatMessage();
        parent::__construct($message, 422);
    }

    private function formatMessage(): string
    {
        $messages = [];
        foreach ($this->violations as $violation) {
            $productName = $violation['product_name'] ?? 'Unknown product';
            $reasons = implode(', ', $violation['violations'] ?? []);
            $messages[] = "{$productName}: {$reasons}";
        }

        return 'Return policy violations: ' . implode('; ', $messages);
    }

    public function getViolations(): array
    {
        return $this->violations;
    }
}
