<?php declare(strict_types=1);

/**
 * ListPsychologists — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listpsychologists
 * @see https://catvrf.ru/docs/listpsychologists
 */


namespace App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\Psychology\PsychologistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

/**
 * Class ListPsychologists
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages
 */
final class ListPsychologists extends ListRecords
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = PsychologistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        \Illuminate\Support\Facades\Log::channel('audit')->info('Accessing Psychologists list', [
            'user_id' => auth()->id(),
            'tenant_id' => $this->guard->user()->tenant_id,
        ]);

        return parent::getTableQuery()->withCount('bookings');
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }
}
