<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FlowersResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ListFlowers extends ListRecords
{
    protected static string $resource = FlowersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый B2B-магазин')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label('Компания')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('company_inn')
                    ->label('ИНН')
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('contact_person')
                    ->label('Контакт')
                    ->searchable(),
                TextColumn::make('contact_phone')
                    ->label('Телефон')
                    ->copyable(),
                TextColumn::make('min_order_items')
                    ->label('Мин. заказ')
                    ->alignCenter(),
                IconColumn::make('is_verified')
                    ->label('Проверен')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Активен'),
                TernaryFilter::make('is_verified')->label('Проверен'),
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
