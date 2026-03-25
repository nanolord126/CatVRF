declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Policies;

use App\Models\User;
use App\Domains\HomeServices\Models\ServiceListing;
use Illuminate\Auth\Access\Response;

final /**
 * ServiceListingPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ServiceListingPolicy
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, ServiceListing $listing): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_listings') ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function update(User $user, ServiceListing $listing): Response
    {
        return $user->id === $listing->contractor->user_id || $user->hasPermissionTo('update_listings') ? $this->response->allow() : $this->response->deny('Unauthorized');
    }

    public function delete(User $user, ServiceListing $listing): Response
    {
        return $user->id === $listing->contractor->user_id || $user->hasPermissionTo('delete_listings') ? $this->response->allow() : $this->response->deny('Unauthorized');
    }
}
