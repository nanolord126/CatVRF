declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Hotels\Policies;

use App\Domains\Hotels\Models\Booking;
use Illuminate\Auth\Access\Response;

final /**
 * BookingPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BookingPolicy
{
    public function viewAny(): Response
    {
        return auth()->check()
            ? $this->response->allow()
            : $this->response->deny('Not authorized');
    }

    public function view(): Response
    {
        return auth()->check()
            ? $this->response->allow()
            : $this->response->deny('Not authorized');
    }

    public function create(): Response
    {
        return auth()->check()
            ? $this->response->allow()
            : $this->response->deny('Not authorized');
    }

    public function cancel(): Response
    {
        return auth()->check() && (auth()->user()->is_admin || auth()->user()->is_guest)
            ? $this->response->allow()
            : $this->response->deny('Not authorized');
    }

    public function delete(): Response
    {
        return auth()->check() && auth()->user()->is_admin
            ? $this->response->allow()
            : $this->response->deny('Not authorized');
    }
}
