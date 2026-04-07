<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\GroceryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ListGrocery extends ListRecords
{
    protected static string $resource = GroceryResource::class;

    public function getTitle(): string
    {
        return 'Магазины продуктов';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить магазин')
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
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('address')
                    ->label('Адрес')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable(),
                TextColumn::make('review_count')
                    ->label('Отзывы')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_verified')
                    ->label('Верифицирован')
                    ->boolean(),
                TextColumn::make('delivery_radius_km')
                    ->label('Радиус, км')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('commission_percent')
                    ->label('Комиссия, %')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('api_provider')
                    ->label('API')
                    ->toggleable(),
                TextColumn::make('last_sync_at')
                    ->label('Синхронизация')
                    ->dateTime()
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
                TernaryFilter::make('is_verified')
                    ->label('Верификация'),
                SelectFilter::make('api_provider')
                    ->label('API провайдер')
                    ->options([
                        'manual' => 'Manual',
                        'external' => 'External',
                        'partner' => 'Partner',
                    ]),
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
