<?php
namespace App\Domains\Inventory\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['sku' => 'required|unique:inventory_items', 'name' => 'required|string', 'stock' => 'required|integer|min:0'];
    }
}
