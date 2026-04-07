<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\AutoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

final class ListAuto extends ListRecords
{
    protected static string $resource = AutoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить транспорт')
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

                TextColumn::make('brand')
                    ->label('Марка')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('model')
                    ->label('Модель')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('year')
                    ->label('Год')
                    ->sortable(),

                TextColumn::make('color')
                    ->label('Цвет')
                    ->toggleable(),

                TextColumn::make('license_plate')
                    ->label('Гос. номер')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('vin')
                    ->label('VIN')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'primary' => 'taxi',
                        'success' => 'fleet',
                        'warning' => 'private',
                        'danger'  => 'sale',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'fleet'   => 'Флот',
                        'private' => 'Частное',
                        'sale'    => 'Продажа',
                        default   => $state,
                    }),

                BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'active',
                        'warning' => fn ($state) => in_array($state, ['repair', 'wash']),
                        'primary' => 'ride',
                        'danger'  => 'sold',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'repair'     => 'Ремонт',
                        'sold'       => 'Продан',
                        'wash'       => 'Мойка',
                        'ride'       => 'В рейсе',
                        default      => $state,
                    }),

                TextColumn::make('price_kopecks')
                    ->label('Цена')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 0, ',', ' ') . ' ₽' : '—'),

                TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(16),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип ТС')
                    ->options([
                        'taxi'    => 'Такси',
                        'fleet'   => 'Флот',
                        'private' => 'Частное',
                        'sale'    => 'На продажу',
                    ]),

                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активен',
                        'repair' => 'Ремонт',
                        'sold'   => 'Продан',
                        'wash'   => 'Мойка',
                        'ride'   => 'В рейсе',
                    ]),

                SelectFilter::make('brand')
                    ->label('Марка')
                    ->searchable()
                    ->relationship('brand', 'brand'),
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
