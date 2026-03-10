<?php
namespace App\Domains\Geo\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreGeoZoneRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['latitude' => 'required|numeric', 'longitude' => 'required|numeric', 'radius' => 'required|numeric|min:1'];
    }
}
