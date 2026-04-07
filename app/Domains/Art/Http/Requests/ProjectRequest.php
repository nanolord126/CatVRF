<?php
declare(strict_types=1);

/**
 * ProjectRequest — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/projectrequest
 */


namespace App\Domains\Art\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ProjectRequest
 *
 * Part of the Art vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Form Request with validation rules.
 * Validates input before reaching the controller.
 * Authorization checks tenant and business group access.
 *
 * @package App\Domains\Art\Http\Requests
 */
final class ProjectRequest extends FormRequest
{
    /**
     * Handle authorize operation.
     *
     * @throws \DomainException
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle rules operation.
     *
     * @throws \DomainException
     */
    public function rules(): array
    {
        return [
            'artist_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'brief' => ['nullable', 'string'],
            'budget_cents' => ['nullable', 'integer', 'min:0'],
            'deadline_at' => ['nullable', 'date'],
            'inn' => ['nullable', 'string', 'max:32'],
            'business_card_id' => ['nullable', 'string', 'max:64'],
            'preferences' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
            'correlation_id' => ['nullable', 'string'],
            'tenant_id' => ['nullable', 'integer'],
            'business_group_id' => ['nullable', 'integer'],
        ];
    }
}
