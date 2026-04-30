<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ProfileType;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

/**
 * Simple HTTP Exceptions Collection
 * 
 * Consolidated small exception classes that follow the same pattern:
 * - Simple constructor with message and code
 * - render() method returning JSON response
 * 
 * This reduces file count while maintaining clear separation of concerns.
 */

final class ProfileTransitionNotAllowedException extends \Exception
{
    public function __construct(
        string $message = 'Profile transition not allowed',
        int $code = Response::HTTP_FORBIDDEN,
    ) {
        parent::__construct($message, $code);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'profile_transition_not_allowed',
            'message' => $this->getMessage(),
        ], $this->code);
    }
}

final class ContactLockedException extends \Exception
{
    public function __construct(
        string $message = 'Contact is temporarily locked',
        int $code = Response::HTTP_LOCKED,
    ) {
        parent::__construct($message, $code);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'contact_locked',
            'message' => $this->getMessage(),
        ], $this->code);
    }
}

final class ContactAlreadyUsedException extends \Exception
{
    public function __construct(
        string $message,
        public readonly ?ProfileType $existingType = null,
        int $code = Response::HTTP_FORBIDDEN,
    ) {
        parent::__construct($message, $code);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'contact_already_used',
            'message' => $this->getMessage(),
            'existing_type' => $this->existingType?->value,
        ], $this->code);
    }
}

final class RoleLimitExceededException extends \Exception
{
    public function __construct(
        string $message,
        public readonly int $currentCount,
        public readonly int $limit,
        int $code = Response::HTTP_FORBIDDEN,
    ) {
        parent::__construct($message, $code);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'role_limit_exceeded',
            'message' => $this->getMessage(),
            'current_count' => $this->currentCount,
            'limit' => $this->limit,
        ], $this->code);
    }
}
