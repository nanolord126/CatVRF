<?php
namespace App\Domains\RealEstate\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['address' => 'required|string', 'price' => 'required|numeric|min:1', 'area' => 'required|numeric|min:1'];
    }
}
