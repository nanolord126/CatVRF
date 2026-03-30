<?php declare(strict_types=1);

namespace App\Domains\Archived\Photography\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PhotoSessionPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response


    	{


    		return $this->response->allow();


    	}


    	public function view(User $user, PhotoSession $session): Response


    	{


    		return $user->id === $session->user_id || $user->is_admin


    			? $this->response->allow()


    			: $this->response->deny('Нет доступа');


    	}


    	public function create(User $user): Response


    	{


    		return $user->tenant_id ? $this->response->allow() : $this->response->deny('Требуется tenant');


    	}


    	public function cancel(User $user, PhotoSession $session): Response


    	{


    		return ($user->id === $session->user_id || $user->is_admin) && in_array($session->status, ['pending', 'confirmed'])


    			? $this->response->allow()


    			: $this->response->deny('Отмена невозможна');


    	}
}
