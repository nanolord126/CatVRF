<?php declare(strict_types=1);

namespace App\Domains\Hotels\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReviewPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
