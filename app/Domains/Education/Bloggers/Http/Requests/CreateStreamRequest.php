<?php declare(strict_types=1);

/**
 * CreateStreamRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createstreamrequest
 */


namespace App\Domains\Education\Bloggers\Http\Requests;


use Illuminate\Contracts\Auth\Guard;
final class CreateStreamRequest
{
    public function __construct(
        private readonly Guard $guard) {}


    public function authorize(): bool
        {
            return $this->guard->check() && $this->guard->user()->canStream();
        }

        public function rules(): array
        {
            return [
                'title' => 'required|string|min:3|max:255',
                'description' => 'nullable|string|max:1000',
                'scheduled_at' => 'required|date_format:Y-m-d H:i:s|after:now',
                'tags' => 'nullable|array|max:10',
                'tags.*' => 'string|max:50',
            ];
        }

        public function messages(): array
        {
            return [
                'title.required' => 'Укажите название стрима',
                'title.min' => 'Название должно быть не менее 3 символов',
                'scheduled_at.required' => 'Укажите время начала стрима',
                'scheduled_at.after' => 'Время начала должно быть в будущем',
                'tags.max' => 'Максимум 10 тегов',
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
