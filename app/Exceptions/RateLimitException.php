<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

final class RateLimitException extends Exception
{
    private int $retryAfter;
    
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $retryAfter = 60,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->retryAfter = $retryAfter;
    }
    
    public function render()
    {
        return response()->json([
            'error' => 'Too many requests',
            'message' => $this->message,
        ], $this->response->HTTP_TOO_MANY_REQUESTS)  // 429
            ->header('Retry-After', $this->retryAfter)
            ->header('X-RateLimit-Reset', now()->addSeconds($this->retryAfter)->timestamp);
    }
}
