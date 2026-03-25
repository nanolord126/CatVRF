declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Hotels\Policies;

use App\Domains\Hotels\Models\Review;
use Illuminate\Auth\Access\Response;

final /**
 * ReviewPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ReviewPolicy
{
    public function viewAny(): Response
    {
        return $this->response->allow();
    }

    public function view(): Response
    {
        return $this->response->allow();
    }

    public function create(): Response
    {
        return auth()->check()
            ? $this->response->allow()
            : $this->response->deny('Not authorized');
    }

    public function update(): Response
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
