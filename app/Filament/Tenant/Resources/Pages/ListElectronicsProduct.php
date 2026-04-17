<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use Filament\Resources\Pages\ListRecords;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;


/**
 * Class ListElectronicsProduct
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class ListElectronicsProduct extends ListRecords
{
    protected static string $resource = ElectronicsProductResource::class;

    /**
     * Handle getTitle operation.
     *
     * @throws \DomainException
     */
    public function getTitle(): string
    {
        return 'List ElectronicsProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Handle table operation.
     *
     * @throws \DomainException
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
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
