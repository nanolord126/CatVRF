<?php declare(strict_types=1);

namespace App\Domains\Photography\Policies;

final class PhotoStudioPolicy
{

    public function viewAny(User $user): Response
    	{
    		return $this->response->allow();
    	}

    	public function view(User $user, PhotoStudio $studio): Response
    	{
    		return $user->id === $studio->user_id || $user->is_admin
    			? $this->response->allow()
    			: $this->response->deny('Нет доступа');
    	}

    	public function create(User $user): Response
    	{
    		return $user->tenant_id ? $this->response->allow() : $this->response->deny('Требуется tenant');
    	}

    	public function update(User $user, PhotoStudio $studio): Response
    	{
    		return $user->id === $studio->user_id || $user->is_admin
    			? $this->response->allow()
    			: $this->response->deny('Нет доступа');
    	}

    	public function delete(User $user, PhotoStudio $studio): Response
    	{
    		return $user->id === $studio->user_id || $user->is_admin
    			? $this->response->allow()
    			: $this->response->deny('Нет доступа');
    	}

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
