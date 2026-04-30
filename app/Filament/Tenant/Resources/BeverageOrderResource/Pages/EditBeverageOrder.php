<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageOrderResource\Pages;

use Carbon\CarbonImmutable;

use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\EditRecord;

final class EditBeverageOrder extends EditRecord
{
    protected static string $resource = BeverageOrderResource::class;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

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

    protected function afterSave(): void
    {
        $this->logger->$this->logger->info('Beverage Order Status Updated', [
            'order_id' => $this->record->id,
            'tenant_id' => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
            'status' => $this->record->status,
            'user_id' => auth()->id(),
        ]);
    }
}
