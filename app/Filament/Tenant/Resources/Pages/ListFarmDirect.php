<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FarmDirectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ListFarmDirect extends ListRecords
{
    protected static string $resource = FarmDirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый продукт')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('category')
                    ->label('Категория')
                    ->searchable()
                    ->badge(),
                TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 2, '.', ' ') . ' ₽')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([])
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
