<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrPropertyResource\Pages;


use Illuminate\Http\Request;
use App\Filament\Tenant\Resources\StrPropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

/**
 * Class EditStrProperty
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\StrPropertyResource\Pages
 */
final class EditStrProperty extends EditRecord
{
    public function __construct(
        private readonly Request $request,
    ) {}

    protected static string $resource = StrPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = $this->request->header('X-Correlation-ID', (string) Str::uuid());

        return $data;
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

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
