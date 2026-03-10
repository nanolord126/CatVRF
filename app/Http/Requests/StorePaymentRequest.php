<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['amount' => 'required|numeric|min:1', 'method' => 'required|in:card,wallet,bank'];
    }
}
