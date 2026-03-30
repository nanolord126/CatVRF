<?php declare(strict_types=1);

namespace App\Filament\Widgets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TeamPresenceWidget extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                \Illuminate\Support\Facades\Log::channel('audit')->error('Failed to load team presence data', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        public function refresh(): void
        {
            $this->loadData();
        }
}
