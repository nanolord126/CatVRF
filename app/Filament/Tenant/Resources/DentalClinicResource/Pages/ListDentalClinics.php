<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinicResource\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Illuminate\Http\Request;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Log\LogManager;

final class ListDentalClinics extends ListRecords
{
    protected static string $resource = DentalClinicResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly LogManager $log,) {}

    public function mount(): void
    {
        parent::mount();

        $this->log->channel('audit')->$this->logger->info('Dental Clinic Directory accessed', [
            'tenant_id' => tenant()->id ?? 'system',
            'user_id' => auth()->id(),
            'correlation_id' => $this->request->header('X-Correlation-ID'),
        ]);
    }

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
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label('Register New Clinic'),
        ];
    }
}
