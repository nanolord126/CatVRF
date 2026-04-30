<?php

declare(strict_types=1);

namespace Modules\Marketplace\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request для поиска позиций на маркетплейсе
 */
final class SearchListingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'query' => 'nullable|string|max:255',
            'vertical' => 'nullable|string|in:beauty,restaurant,fashion,hotels,fitness,flowers,dental,vet_grooming,taxi,real_estate,supermarket,auto,media,video,custom',
            'category' => 'nullable|string|max:100',
            'type' => 'nullable|string|in:product,service,booking,subscription,digital,bundle,experience',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'in_stock' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'tenant_id' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:ranking_score,price,created_at,popularity_score,rating',
            'sort_order' => 'nullable|string|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'query.max' => 'Search query must not exceed 255 characters',
            'vertical.in' => 'Invalid vertical source',
            'category.max' => 'Category must not exceed 100 characters',
            'type.in' => 'Invalid listing type',
            'min_price.numeric' => 'Min price must be a number',
            'min_price.min' => 'Min price cannot be negative',
            'max_price.numeric' => 'Max price must be a number',
            'max_price.min' => 'Max price cannot be negative',
            'min_rating.numeric' => 'Min rating must be a number',
            'min_rating.min' => 'Min rating cannot be negative',
            'min_rating.max' => 'Min rating cannot exceed 5',
            'tags.*.max' => 'Each tag must not exceed 50 characters',
            'sort_by.in' => 'Invalid sort field',
            'sort_order.in' => 'Sort order must be asc or desc',
            'page.min' => 'Page must be at least 1',
            'per_page.min' => 'Per page must be at least 1',
            'per_page.max' => 'Per page cannot exceed 100',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422)
        );
    }

    public function getValidatedData(): array
    {
        return $this->validated();
    }
}
