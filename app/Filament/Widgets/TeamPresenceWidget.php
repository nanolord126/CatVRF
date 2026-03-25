declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\TeamPresenceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

final /**
 * TeamPresenceWidget
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TeamPresenceWidget extends Widget
{
    protected static string $view = 'filament.widgets.team-presence-widget';

    public array $teamMembers = [];
    public int $onlineCount = 0;
    public int $totalCount = 0;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        try {
            $tenantId = filament()->getTenant()->id;

            // В реальности здесь должна быть логика получения командных данных
            // из Tenant или BusinessGroup
            $this->teamMembers = [];
            $this->onlineCount = 0;
            $this->totalCount = 0;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\$this->log->channel('audit')->error('Failed to load team presence data', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function refresh(): void
    {
        $this->loadData();
    }
}
