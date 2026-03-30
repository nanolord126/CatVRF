<?php declare(strict_types=1);

namespace App\Domains\Archived\Photography\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BPhotoOrderPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response


    	{


    		return $this->response->allow();


    	}


    	public function view(User $user, B2BPhotoOrder $order): Response


    	{


    		return $user->tenant_id === $order->tenant_id || $user->is_admin


    			? $this->response->allow()


    			: $this->response->deny('Нет доступа');


    	}


    	public function approve(User $user, B2BPhotoOrder $order): Response


    	{


    		return ($user->tenant_id === $order->tenant_id || $user->is_admin) && $order->status === 'pending'


    			? $this->response->allow()


    			: $this->response->deny('Одобрение невозможно');


    	}


    	public function reject(User $user, B2BPhotoOrder $order): Response


    	{


    		return ($user->tenant_id === $order->tenant_id || $user->is_admin) && $order->status === 'pending'


    			? $this->response->allow()


    			: $this->response->deny('Отклонение невозможно');


    	}
}
