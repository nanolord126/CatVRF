declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Flowers\Policies;

use App\Domains\Flowers\Models\B2BFlowerStorefront;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * B2BFlowerStorefrontPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BFlowerStorefrontPolicy
{
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
