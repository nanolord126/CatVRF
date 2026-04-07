<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageReviewResource\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;

final class CreateBeverageReview extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = BeverageReviewResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string) Str::uuid();
            $data['tenant_id'] = tenant()->id;
            $data['correlation_id'] = (string) Str::uuid();

            return $data;
        }

        protected function afterCreate(): void
        {
            $this->logger->info('Beverage Review Manual Entry Recorded', [
                'review_id' => $this->record->id,
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
