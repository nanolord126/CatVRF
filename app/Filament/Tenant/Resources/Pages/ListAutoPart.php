<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\AutoPartResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ListAutoPart extends ListRecords
{
    protected static string $resource = AutoPartResource::class;

    public function getTitle(): string
    {
        return 'Запчасти';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить запчасть')
                ->icon('heroicon-m-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('auto_catalog_brand_id')
                    ->label('Бренд')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('price_kopecks')
                    ->label('Цена')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),
                TextColumn::make('wholesale_price_kopecks')
                    ->label('Опт')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->toggleable(),
                TextColumn::make('stock_quantity')
                    ->label('Остаток')
                    ->sortable(),
                TextColumn::make('min_threshold')
                    ->label('Мин. остаток')
                    ->toggleable(),
                TextColumn::make('category')
                    ->label('Категория')
                    ->toggleable(),
                TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
