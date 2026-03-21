<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

final class InvalidPayloadException extends Exception
{
    public function __construct(
        string $message = 'Invalid payload signature',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    public function render()
    {
        return response()->json([
            'error' => 'Invalid payload',
            'message' => $this->message,
        ], Response::HTTP_BAD_REQUEST);  // 400
    }
}
