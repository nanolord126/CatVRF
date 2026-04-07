<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BeverageSubscriptionResource;
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
use Filament\Tables\Table;

final class ListBeverageSubscription extends ListRecords
{
    protected static string $resource = BeverageSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Создать подписку')->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('plan_type')->label('Тариф')
                    ->colors([
                        'primary' => 'daily_coffee',
                        'warning' => 'weekly_bar',
                        'success' => 'monthly_tea',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'weekly_bar'   => 'Еженедельный бар',
                        'monthly_tea'  => 'Ежемесячный чай',
                        default        => $state,
                    }),
                TextColumn::make('price')->label('Цена')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 0, ',', ' ') . ' ₽')->sortable(),
                TextColumn::make('usage')->label('Использовано')
                    ->getStateUsing(fn ($record) => $record->used_count . ' / ' . $record->limit_count),
                TextColumn::make('expires_at')->label('Истекает')->dateTime('d.m.Y H:i')->sortable(),
                IconColumn::make('auto_renew')->label('Автопродление')->boolean()
                    ->trueIcon('heroicon-o-arrow-path')->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')->falseColor('gray'),
                BadgeColumn::make('status')->label('Статус')
                    ->colors(['success' => 'active', 'danger' => 'cancelled', 'warning' => 'expired'])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'cancelled' => 'Отменена',
                        'expired'   => 'Истекла',
                        default     => $state,
                    }),
                TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('plan_type')->label('Тариф')
                    ->options(['daily_coffee' => 'Ежедневный кофе', 'weekly_bar' => 'Еженедельный бар', 'monthly_tea' => 'Ежемесячный чай']),
                SelectFilter::make('status')->label('Статус')
                    ->options(['active' => 'Активна', 'cancelled' => 'Отменена', 'expired' => 'Истекла']),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc')->striped();
    }
}
