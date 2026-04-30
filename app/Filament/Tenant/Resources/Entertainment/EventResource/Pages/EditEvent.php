<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\EventResource\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Log\LogManager;

final class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    public function __construct(private readonly LoggerInterface $logger,
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

    protected function beforeSave(): void
    {
        $this->log->channel('audit')->$this->logger->info('Entertainment Event modification started', [
            'event_id' => $this->record->id,
            'user_id' => auth()->id(),
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    protected function afterSave(): void
    {
        $this->log->channel('audit')->$this->logger->info('Entertainment Event modification completed', [
            'event_id' => $this->record->id,
            'user_id' => auth()->id(),
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
