<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageShopResource\Pages;




use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;

final class CreateBeverageShop extends CreateRecord
{
    public function __construct(
        private readonly Request $request,
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = BeverageShopResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string) Str::uuid();
            $data['tenant_id'] = tenant()->id;
            $data['correlation_id'] = $this->request->header('X-Correlation-ID', (string) Str::uuid());

            return $data;
        }

        protected function afterCreate(): void
        {
            \Illuminate\Support\Facades\Log::channel('audit')->info('Beverage Shop Registered', [
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
