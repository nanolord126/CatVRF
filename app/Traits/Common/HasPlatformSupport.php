<?php

namespace App\Traits\Common;

use App\Models\Tenant;
use App\Services\Common\Support\HelpdeskService;
use Illuminate\Support\Facades\App;

trait HasPlatformSupport
{
    /**
     * Быстрое создание тикета в поддержку для текущей записи (Taxi order, Clinic appointment и т.д.)
     */
    public function openSupportTicket(string $subject, string $category = 'technical', string $priority = 'medium')
    {
        $helpdesk = App::make(HelpdeskService::class);
        $tenant = (method_exists($this, 'tenant')) ? $this->tenant : tenant();
        $userId = auth()->id() ?? $this->user_id ?? 1;

        $ticketId = $helpdesk->openTicket($tenant, $userId, [
            'subject' => "[Auto-Ticket] {$subject} (ID: {$this->id})",
            'category' => $category,
            'priority' => $priority
        ]);

        return $ticketId;
    }

    /**
     * Получить или создать чат для этой записи (например, чат между водителем и пассажиром в Taxi)
     */
    public function getSupportChat(int $toUserId, ?string $context = null)
    {
        $helpdesk = App::make(HelpdeskService::class);
        
        return $helpdesk->findOrCreatePlatformChat(
            auth()->id(),
            $toUserId,
            tenant('id'),
            tenant('id'),
            $context ?? get_class($this),
            $this->id
        );
    }
}
