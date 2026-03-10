<?php
namespace App\Domains\Delivery\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['recipient_id' => 'required|exists:users,id', 'address' => 'required|string', 'items' => 'required|array'];
    }
}
