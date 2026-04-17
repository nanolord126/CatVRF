<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\File;

final class MatchMastersByPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'photo' => [
                'required',
                File::image()
                    ->max(10240)
                    ->mimes('jpg,jpeg,png,webp'),
            ],
            'service_type' => ['nullable', 'string', 'in:haircut,coloring,styling,makeup,facial,nails,spa'],
            'preferred_gender' => ['nullable', 'string', 'in:male,female,any'],
            'max_distance' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'price_min' => ['nullable', 'integer', 'min:0'],
            'price_max' => ['nullable', 'integer', 'min:0', 'gt:price_min'],
            'inn' => ['nullable', 'string', 'regex:/^\d{10,12}$/'],
            'business_card_id' => ['nullable', 'integer', 'exists:business_groups,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'User not found',
            'photo.required' => 'Photo is required',
            'photo.image' => 'Photo must be an image',
            'photo.max' => 'Photo size must not exceed 10MB',
            'photo.mimes' => 'Photo must be jpg, jpeg, png or webp',
            'service_type.in' => 'Invalid service type',
            'preferred_gender.in' => 'Invalid gender preference',
            'max_distance.numeric' => 'Max distance must be a number',
            'max_distance.max' => 'Max distance must not exceed 100km',
            'min_rating.numeric' => 'Min rating must be a number',
            'min_rating.max' => 'Min rating must not exceed 5',
            'price_max.gt' => 'Max price must be greater than min price',
            'inn.regex' => 'Invalid INN format',
            'business_card_id.exists' => 'Business card not found',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'correlation_id' => $this->header('X-Correlation-ID'),
            ], 422)
        );
    }
}
