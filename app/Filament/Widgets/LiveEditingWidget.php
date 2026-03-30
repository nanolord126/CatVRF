<?php declare(strict_types=1);

namespace App\Filament\Widgets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LiveEditingWidget extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $view = 'filament.widgets.live-editing-widget';

        public array $activeEditors = [];
        public array $activePresence = [];
        public int $editorCount = 0;
        public int $presenceCount = 0;

        public function mount(): void
        {
            $this->loadData();
        }

        public function loadData(): void
        {
            try {
                $tenantId = filament()->getTenant()->id;

                // Получаем активных редакторов
                // Это примерные данные - в реальности нужно передавать documentType и documentId
                $this->activeEditors = [];
                $this->editorCount = 0;

                // Получаем присутствие
                $this->activePresence = [];
                $this->presenceCount = 0;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error('Failed to load live editing data', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        public function refresh(): void
        {
            $this->loadData();
        }
}
