<?php declare(strict_types=1);

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class CreatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (class_exists(\App\Services\Fraud\FraudControlService::class) && Auth::check()) {
            $fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass());
            if ($fraudScore > 0.7 && !Auth::user()->hasRole('admin')) {
                \Illuminate\Support\Facades\Log::channel('audit')->warning('Fraud check blocked request', ['class' => __CLASS__, 'score' => $fraudScore]);
                return false;
            }
        }
        return Auth::check() && Auth::user()->tenant_id === tenant()->id;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lon' => ['nullable', 'numeric', 'between:-180,180'],
            'property_type' => ['required', 'in:apartment,house,commercial,land,parking,warehouse'],
            'price' => ['required', 'numeric', 'min:0'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'rooms' => ['nullable', 'integer', 'min:1', 'max:20'],
            'floor' => ['nullable', 'integer', 'min:1'],
            'total_floors' => ['nullable', 'integer', 'min:1'],
            'year_built' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'features' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'virtual_tour_url' => ['nullable', 'url', 'max:500'],
            'ar_model_url' => ['nullable', 'url', 'max:500'],
            'document_hashes' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Title is required',
            'address.required' => 'Address is required',
            'city.required' => 'City is required',
            'property_type.required' => 'Property type is required',
            'property_type.in' => 'Invalid property type',
            'price.required' => 'Price is required',
            'price.min' => 'Price must be positive',
            'lat.between' => 'Latitude must be between -90 and 90',
            'lon.between' => 'Longitude must be between -180 and 180',
            'year_built.min' => 'Year built must be after 1900',
            'year_built.max' => 'Year built cannot be in the future',
        ];
    }
}
