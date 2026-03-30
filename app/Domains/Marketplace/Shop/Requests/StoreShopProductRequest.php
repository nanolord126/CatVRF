<?php declare(strict_types=1);

namespace App\Domains\Marketplace\Shop\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StoreShopProductRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            return auth()->user()->hasRole(['business_owner', 'manager']);
        }

        public function rules(): array
        {
            return [
                'name' => ['required', 'string', 'max:255'],
                'sku' => ['required', 'string', 'unique:shop_products,sku,NULL,id,tenant_id,' . auth()->user()->tenant_id],
                'category' => ['required', 'string', 'in:clothes,shoes,kids,etc'],
                'price' => ['required', 'integer', 'min:0'], // В копейках
                'attributes' => ['nullable', 'array'],
            ];
        }

        public function messages(): array
        {
            return [
                'sku.unique' => 'Товар с таким SKU уже существует в вашем магазине.',
            ];
        }
}
