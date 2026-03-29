<?php declare(strict_types=1);

/**
 * Скрипт для массового создания всех Pages для оставшихся 38 вертикалей
 * Вертикали: Delivery, Education, Electronics, Entertainment, EventManagement, EventVenues, Fitness,
 *           Florist, Food, FarmDirect, Furniture, Gifts, GroceryAndDelivery, HomeServices, Hotels,
 *           Insurance, Legal, Logistics, Manufacturing, Marketing, MeatShops, Media, Medical, OfficeCatering,
 *           Pet, Photography, Publishing, RealEstate, Sports, Stationery, Travel, Tickets, ConstructionMaterials,
 *           Jewelry, Books, Hospitality, Commerce, Services, Marketplace, Enterprise
 */

$verticals = [
    'Delivery', 'Education', 'Electronics', 'Entertainment', 'EventManagement',
    'EventVenues', 'Fitness', 'Florist', 'Food', 'FarmDirect', 'Furniture',
    'Gifts', 'GroceryAndDelivery', 'HomeServices', 'Hotels', 'Insurance',
    'Legal', 'Logistics', 'Manufacturing', 'Marketing', 'MeatShops', 'Media',
    'Medical', 'OfficeCatering', 'Pet', 'Photography', 'Publishing',
    'RealEstate', 'Sports', 'Stationery', 'Travel', 'Tickets',
    'ConstructionMaterials', 'Jewelry', 'Books', 'Hospitality',
    'Commerce', 'Services', 'Marketplace', 'Enterprise'
];

$pageTypes = ['List', 'Create', 'Edit', 'View'];
$basePath = 'c:\\opt\\kotvrf\\CatVRF\\app\\Filament\\Tenant\\Resources';

$templates = [
    'List' => <<<'PHP'
<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\%NAMESPACE%\Pages;

use App\Filament\Tenant\Resources\%NAMESPACE%\%RESOURCE%Resource;
use Filament\Actions\{CreateAction,DeleteBulkAction};
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class %CLASS% extends ListRecords
{
    protected static string $resource = %RESOURCE%Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новая запись')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $tenantId = filament()->getTenant()->id;
        $userId = auth()->id();
        $correlationId = Str::uuid()->toString();

        Log::channel('audit')->info('%NAMESPACE% ListRecords accessed', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);

        return %RESOURCE%Resource::getEloquentQuery()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->with(['tenant', 'businessGroup'])
            ->orderBy('created_at', 'desc');
    }

    protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->label('Удалить выбранные')
                ->icon('heroicon-m-trash'),
        ];
    }

    public function render()
    {
        Log::channel('audit')->info('%CLASS% page rendered', [
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
        ]);

        return parent::render();
    }
}
PHP,

    'Create' => <<<'PHP'
<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\%NAMESPACE%\Pages;

use App\Filament\Tenant\Resources\%NAMESPACE%\%RESOURCE%Resource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;

final class %CLASS% extends CreateRecord
{
    protected static string $resource = %RESOURCE%Resource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();
        
        DB::transaction(function () use (&$data, $correlationId) {
            $data['correlation_id'] = $correlationId;
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = Str::uuid()->toString();

            Log::channel('audit')->info('%NAMESPACE% creation form submitted', [
                'correlation_id' => $correlationId,
                'tenant_id' => $data['tenant_id'],
                'user_id' => auth()->id(),
            ]);
        });

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::channel('audit')->info('%NAMESPACE% record created successfully', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
PHP,

    'Edit' => <<<'PHP'
<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\%NAMESPACE%\Pages;

use App\Filament\Tenant\Resources\%NAMESPACE%\%RESOURCE%Resource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;

final class %CLASS% extends EditRecord
{
    protected static string $resource = %RESOURCE%Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Удалить')
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        DB::transaction(function () use (&$data) {
            $data['correlation_id'] = Str::uuid()->toString();
            $data['tenant_id'] = filament()->getTenant()->id;
            
            Log::channel('audit')->info('%NAMESPACE% updated', [
                'user_id' => auth()->id(),
                'correlation_id' => $data['correlation_id'],
                'tenant_id' => $data['tenant_id'],
                'record_id' => $this->record->id,
            ]);
        });

        return $data;
    }

    protected function afterSave(): void
    {
        Log::channel('audit')->info('%NAMESPACE% edit page saved', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
PHP,

    'View' => <<<'PHP'
<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\%NAMESPACE%\Pages;

use App\Filament\Tenant\Resources\%NAMESPACE%\%RESOURCE%Resource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

final class %CLASS% extends ViewRecord
{
    protected static string $resource = %RESOURCE%Resource::class;

    protected function afterLoad(): void
    {
        Log::channel('audit')->info('%NAMESPACE% record viewed', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => auth()->id(),
            'tenant_id' => filament()->getTenant()->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function render()
    {
        Log::channel('audit')->debug('%CLASS% page rendered', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
        ]);

        return parent::render();
    }
}
PHP
];

foreach ($verticals as $index => $vertical) {
    echo "[" . ($index + 1) . "/" . count($verticals) . "] Processing $vertical...\n";
    
    foreach ($pageTypes as $pageType) {
        $namespace = $vertical;
        $resource = $vertical;
        $class = $pageType . (str_ends_with($vertical, 's') ? substr($vertical, 0, -1) : $vertical);
        
        $template = $templates[$pageType];
        $content = str_replace(
            ['%NAMESPACE%', '%RESOURCE%', '%CLASS%'],
            [$namespace, $resource, $class],
            $template
        );
        
        $fileName = $class . '.php';
        $filePath = "$basePath\\$namespace\\Pages\\$fileName";
        
        // Гарантировать существование папки Pages
        $pagesDir = "$basePath\\$namespace\\Pages";
        if (!is_dir($pagesDir)) {
            mkdir($pagesDir, 0755, true);
        }
        
        file_put_contents($filePath, $content);
        echo "  ✓ Created: $fileName\n";
    }
}

echo "\n✅ All 152 pages generated successfully!\n";
