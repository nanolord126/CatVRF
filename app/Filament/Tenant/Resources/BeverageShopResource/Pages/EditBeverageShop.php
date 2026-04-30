<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageShopResource\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Illuminate\Http\Request;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Log\LogManager;

final class EditBeverageShop extends EditRecord
{
    protected static string $resource = BeverageShopResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly LogManager $log,) {}

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = $this->request->header('X-Correlation-ID', (string) Str::uuid());

        return $data;
    }

    protected function afterSave(): void
    {
        $this->log->channel('audit')->$this->logger->info('Beverage Shop Updated', [
            'shop_id' => $this->record->id,
            'tenant_id' => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
