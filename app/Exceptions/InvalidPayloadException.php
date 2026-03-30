<?php declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InvalidPayloadException extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
            ], $this->response->HTTP_BAD_REQUEST);  // 400
        }
}
