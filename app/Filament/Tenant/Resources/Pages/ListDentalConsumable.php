<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\DentalConsumableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Class ListDentalConsumable
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class ListDentalConsumable extends ListRecords
{
    protected static string $resource = DentalConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Добавить расходник')->icon('heroicon-o-plus'),
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
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->label('Название')->sortable()->searchable()->weight('bold'),
                TextColumn::make('sku')->label('SKU')->searchable()->copyable()->badge()->color('gray'),
                TextColumn::make('current_stock')->label('Остаток')->sortable()
                    ->color(fn ($state, $record) => $state <= ($record->min_threshold ?? 10) ? 'danger' : 'success'),
                TextColumn::make('min_threshold')->label('Мин. порог')->sortable(),
                TextColumn::make('correlation_id')->label('Corr. ID')->toggleable(isToggledHiddenByDefault: true)->limit(16),
                TextColumn::make('created_at')->label('Добавлено')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('current_stock', 'asc')->striped();
    }
}
