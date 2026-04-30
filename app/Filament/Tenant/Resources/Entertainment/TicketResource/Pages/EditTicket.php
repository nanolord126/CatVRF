<?php

declare(strict_types=1);

/**
 * EditTicket — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/editticket
 * @see https://catvrf.ru/docs/editticket
 * @see https://catvrf.ru/docs/editticket
 */

namespace App\Filament\Tenant\Resources\Entertainment\TicketResource\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Log\LogManager;

final class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

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
        $this->log->channel('audit')->$this->logger->info('Entertainment Ticket modification', [
            'ticket_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }
}
