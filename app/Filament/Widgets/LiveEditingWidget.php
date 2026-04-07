<?php declare(strict_types=1);

namespace App\Filament\Widgets;


use Psr\Log\LoggerInterface;
use Filament\Widgets\Widget;

/**
 * Class LiveEditingWidget
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Widgets
 */
final class LiveEditingWidget extends Widget
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

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
                $this->logger->error('Failed to load live editing data', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        public function refresh(): void
        {
            $this->loadData();
        }
}
