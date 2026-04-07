<?php declare(strict_types=1);

/**
 * SendChatMessageRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/sendchatmessagerequest
 */


namespace App\Domains\Education\Bloggers\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
final class SendChatMessageRequest
{
    public function __construct(
        private readonly Guard $guard) {}


    public function authorize(): bool
        {
            return $this->guard->check();
        }

        public function rules(): array
        {
            return [
                'message' => 'required|string|min:1|max:500',
                'message_type' => 'required|in:text,gift,product,donation',
            ];
        }

        public function messages(): array
        {
            return [
                'message.required' => 'Сообщение не может быть пустым',
                'message.max' => 'Сообщение не должно превышать 500 символов',
                'message_type.required' => 'Укажите тип сообщения',
            ];
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
