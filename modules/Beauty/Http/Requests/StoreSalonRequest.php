<?php
namespace App\Domains\Beauty\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreSalonRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['name' => 'required|string', 'address' => 'required|string', 'phone' => 'required|string'];
    }
}
