<?php
namespace App\Domains\Education\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['title' => 'required|string', 'instructor_id' => 'required|exists:users,id', 'description' => 'required|string'];
    }
}
