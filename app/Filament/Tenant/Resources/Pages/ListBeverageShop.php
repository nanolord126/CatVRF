<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BeverageShopResource;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

final class ListBeverageShop extends ListRecords
{
    protected static string $resource = BeverageShopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить заведение')
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
                    ->label('Название')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->address),

                BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'warning' => 'coffee_shop',
                        'success' => 'tea_house',
                        'danger'  => 'bar',
                        'primary' => 'brewery',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'tea_house'   => 'Чайная',
                        'bar'         => 'Бар/Паб',
                        'brewery'     => 'Пивоварня',
                        default       => $state,
                    }),

                TextColumn::make('address')
                    ->label('Адрес')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state > 0 ? '★ ' . number_format((float)$state, 1) : '—'),

                TextColumn::make('review_count')
                    ->label('Отзывов')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
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
                SelectFilter::make('type')
                    ->label('Тип заведения')
                    ->options([
                        'coffee_shop' => 'Кофейня',
                        'tea_house'   => 'Чайная',
                        'bar'         => 'Бар/Паб',
                        'brewery'     => 'Пивоварня',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Статус активности')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
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
