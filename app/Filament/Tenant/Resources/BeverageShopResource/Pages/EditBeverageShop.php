<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageShopResource\Pages;




use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\EditRecord;

final class EditBeverageShop extends EditRecord
{
    public function __construct(
        private readonly Request $request,
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = BeverageShopResource::class;

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
            \Illuminate\Support\Facades\Log::channel('audit')->info('Beverage Shop Updated', [
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
