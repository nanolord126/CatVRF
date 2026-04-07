<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\EventResource\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\EditRecord;

final class EditEvent extends EditRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = EventResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ];
        }

        protected function beforeSave(): void
        {
            $this->logger->info('Entertainment Event modification started', [
                'event_id' => $this->record->id,
                'user_id' => $this->guard->id(),
                'correlation_id' => $this->record->correlation_id,
            ]);
        }

        protected function afterSave(): void
        {
            $this->logger->info('Entertainment Event modification completed', [
                'event_id' => $this->record->id,
                'user_id' => $this->guard->id(),
                'correlation_id' => $this->record->correlation_id,
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
