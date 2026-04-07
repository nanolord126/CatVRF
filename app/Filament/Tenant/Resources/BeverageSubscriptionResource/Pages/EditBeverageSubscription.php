<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageSubscriptionResource\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\EditRecord;

final class EditBeverageSubscription extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = BeverageSubscriptionResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make(),
            ];
        }

        protected function afterSave(): void
        {
            $this->logger->info('Beverage Subscription Configuration Updated', [
                'subscription_id' => $this->record->id,
                'tenant_id' => $this->record->tenant_id,
                'correlation_id' => $this->record->correlation_id,
                'user_id' => $this->guard->id(),
            ]);
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
