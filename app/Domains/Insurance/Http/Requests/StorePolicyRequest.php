<?php
namespace App\Domains\Insurance\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StorePolicyRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['policy_number' => 'required|unique:insurance_policies', 'holder_id' => 'required|exists:users,id', 'amount' => 'required|numeric|min:1'];
    }
}
