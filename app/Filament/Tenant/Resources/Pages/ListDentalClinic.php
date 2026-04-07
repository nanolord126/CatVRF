<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\DentalClinicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

final class ListDentalClinic extends ListRecords
{
    protected static string $resource = DentalClinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить клинику')
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
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('Название клиники')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->license_number ? 'Лицензия: ' . $record->license_number : null),

                TextColumn::make('license_number')
                    ->label('№ лицензии')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('address')
                    ->label('Адрес')
                    ->limit(45)
                    ->searchable(),

                TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state > 0 ? '★ ' . $state . '/100' : '—'),

                IconColumn::make('is_premium')
                    ->label('Премиум')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->sortable(),

                TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(16),

                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_premium')
                    ->label('Премиум-статус')
                    ->trueLabel('Только премиум')
                    ->falseLabel('Стандартные'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
