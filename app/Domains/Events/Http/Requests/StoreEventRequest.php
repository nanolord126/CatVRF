<?php
namespace App\Domains\Events\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreEventRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['title' => 'required|string', 'event_date' => 'required|date|after:today', 'location' => 'required|string'];
    }
}
