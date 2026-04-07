<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FurnitureProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ListFurnitureProduct extends ListRecords
{
    protected static string $resource = FurnitureProductResource::class;

    public function getTitle(): string
    {
        return 'Каталог мебели';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить товар')
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
                TextColumn::make('furniture_store_id')
                    ->label('Магазин')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('furniture_category_id')
                    ->label('Категория')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('price_b2c')
                    ->label('Цена B2C')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),
                TextColumn::make('price_b2b')
                    ->label('Цена B2B')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('stock_quantity')
                    ->label('Остаток')
                    ->sortable(),
                IconColumn::make('is_oversized')
                    ->label('Крупногабарит')
                    ->boolean(),
                IconColumn::make('requires_assembly')
                    ->label('Сборка')
                    ->boolean(),
                TextColumn::make('assembly_cost')
                    ->label('Стоимость сборки')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 100, 2, '.', ' ') . ' ₽' : '—')
                    ->toggleable(),
                TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('requires_assembly')
                    ->label('Требует сборки'),
                TernaryFilter::make('is_oversized')
                    ->label('Крупногабарит'),
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
