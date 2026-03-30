<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SaleListingPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(): bool
        {
            return true;
        }

        public function view(): bool
        {
            return true;
        }

        public function create($user): Response
        {
            return $user?->can('create_sale_listing')
                ? $this->response->allow()
                : $this->response->deny('Нет прав');
        }

        public function update($user, $listing): Response
        {
            return $listing->property->owner_id === $user?->id || $user?->is_admin
                ? $this->response->allow()
                : $this->response->deny('Нет прав');
        }

        public function delete($user, $listing): Response
        {
            return $user?->is_admin
                ? $this->response->allow()
                : $this->response->deny('Только админ');
        }
}
