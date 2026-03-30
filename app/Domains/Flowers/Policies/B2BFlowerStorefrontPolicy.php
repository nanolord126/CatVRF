<?php declare(strict_types=1);

namespace App\Domains\Flowers\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BFlowerStorefrontPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function view(User $user, B2BFlowerStorefront $storefront): Response
        {
            if (!$user->company_inn) {
                return $this->response->deny('Company INN is required');
            }

            if ($user->company_inn === $storefront->company_inn && $storefront->is_active) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot access this B2B storefront');
        }

        public function register(User $user): Response
        {
            if ($user->company_inn && !$user->b2bFlowerStorefront) {
                return $this->response->allow();
            }

            return $this->response->deny('Invalid B2B registration request');
        }

        public function update(User $user, B2BFlowerStorefront $storefront): Response
        {
            if ($user->company_inn === $storefront->company_inn) {
                return $this->response->allow();
            }

            return $this->response->deny('You cannot update this storefront');
        }
}
