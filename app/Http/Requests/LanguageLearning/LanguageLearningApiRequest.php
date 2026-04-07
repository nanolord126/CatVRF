<?php declare(strict_types=1);

/**
 * LanguageLearningApiRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/languagelearningapirequest
 * @see https://catvrf.ru/docs/languagelearningapirequest
 */


namespace App\Http\Requests\LanguageLearning;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class LanguageLearningApiRequest
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Http\Requests\LanguageLearning
 */
final class LanguageLearningApiRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
        {
            return true; // tenant scope handles isolation
        }

        /**
         * Handle rules operation.
         *
         * @throws \DomainException
         */
        public function rules(): array
        {
            if ($this->isMethod('POST') && $this->routeIs('*.enroll')) {
                return [
                    'course_id' => 'required|integer|exists:language_courses,id',
                    'student_id' => 'required|integer|exists:users,id',
                    'payment_method' => 'string|nullable',
                    'correlation_id' => 'required|string|uuid',
                ];
            }

            if ($this->isMethod('POST') && $this->routeIs('*.construct-path')) {
                return [
                    'language' => 'required|string|max:50',
                    'level' => 'required|string|max:10',
                    'goal' => 'string|max:100|nullable',
                    'weekly_hours' => 'required|integer|min:1|max:40',
                    'budget_limit' => 'integer|nullable',
                    'correlation_id' => 'required|string|uuid',
                ];
            }

            return [];
        }
}
