<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BooksResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ListBooks extends ListRecords
{
    protected static string $resource = BooksResource::class;

    public function getTitle(): string
    {
        return 'Книги';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить книгу')
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
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('author')
                    ->label('Автор')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('genre')
                    ->label('Жанр')
                    ->toggleable(),
                TextColumn::make('format')
                    ->label('Формат')
                    ->toggleable(),
                TextColumn::make('price_kopecks')
                    ->label('Цена')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),
                IconColumn::make('is_available')
                    ->label('Доступна')
                    ->boolean(),
                TextColumn::make('publisher_id')
                    ->label('Издатель')
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
                TernaryFilter::make('is_available')
                    ->label('В наличии'),
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
