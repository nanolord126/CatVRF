<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BeautyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ListBeauty extends ListRecords
{
    protected static string $resource = BeautyResource::class;

    public function getTitle(): string
    {
        return 'Салоны красоты';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить салон')
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
                TextColumn::make('email')
                    ->label('Email')
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
                    ->label('Верификация')
                    ->boolean(),
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
                    ->label('Верифицирован'),
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
