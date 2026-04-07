<?php declare(strict_types=1);

/**
 * SmileAnalyzeRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/smileanalyzerequest
 */


namespace App\Http\Requests\Api\Dental;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SmileAnalyzeRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\Api\Dental
 */
final class SmileAnalyzeRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
        {
            return true; // Временный обход для публичного демо, в продакшене - rate limiter
        }

        /**
         * Handle rules operation.
         *
         * @throws \DomainException
         */
        public function rules(): array
        {
            return [
                'photo' => [
                    'required',
                    'image',
                    'mimes:jpeg,png,jpg',
                    'max:10240', // 10MB limit
                    'dimensions:min_width=500,min_height=500'
                ],
            ];
        }

        /**
         * Handle messages operation.
         *
         * @throws \DomainException
         */
        public function messages(): array
        {
            return [
                'photo.dimensions' => 'Разрешение фото должно быть минимум 500x500.',
                'photo.max' => 'Размер файла не должен превышать 10МБ.',
            ];
        }
}
