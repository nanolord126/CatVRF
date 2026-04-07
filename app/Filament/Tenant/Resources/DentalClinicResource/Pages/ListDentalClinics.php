<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinicResource\Pages;




use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\ListRecords;

final class ListDentalClinics extends ListRecords
{
    public function __construct(
        private readonly Request $request,
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = DentalClinicResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->label('Register New Clinic'),
            ];
        }

        public function mount(): void
        {
            parent::mount();

            $this->logger->info('Dental Clinic Directory accessed', [
                'tenant_id' => tenant()->id ?? 'system',
                'user_id' => $this->guard->id(),
                'correlation_id' => $this->request->header('X-Correlation-ID')
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
