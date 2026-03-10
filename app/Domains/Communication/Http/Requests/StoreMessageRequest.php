<?php
namespace App\Domains\Communication\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['recipient_id' => 'required|exists:users,id', 'content' => 'required|string|min:1'];
    }
}
