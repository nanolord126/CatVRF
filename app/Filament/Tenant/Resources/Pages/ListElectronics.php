<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\ElectronicsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ListElectronics extends ListRecords
{
    protected static string $resource = ElectronicsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый товар')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('brand')
                    ->label('Бренд')
                    ->searchable()
                    ->badge(),
                TextColumn::make('category')
                    ->label('Категория')
                    ->badge(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('current_stock')
                    ->label('Остаток')
                    ->color(fn ($state) => match (true) {
                        $state === 0 => 'danger',
                        $state <= 5  => 'warning',
                        default      => 'success',
                    })
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('warranty_months')
                    ->label('Гарантия (мес.)')
                    ->alignCenter(),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'draft',
                        'danger'  => 'archived',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(['active' => 'Активен', 'draft' => 'Черновик', 'archived' => 'Архив']),
                SelectFilter::make('category')
                    ->label('Категория'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->bulkActions([DeleteBulkAction::make()])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
