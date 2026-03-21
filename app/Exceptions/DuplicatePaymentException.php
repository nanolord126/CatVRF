<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

final class DuplicatePaymentException extends Exception
{
    public function __construct(
        string $message = 'Duplicate payment attempt detected',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    public function render()
    {
        return response()->json([
            'error' => 'Duplicate payment attempt',
            'message' => $this->message,
        ], Response::HTTP_CONFLICT);  // 409
    }
}
