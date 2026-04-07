<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BeverageOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ListBeverageOrder extends ListRecords
{
    protected static string $resource = BeverageOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Новый заказ')->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('customer_id')->label('Клиент ID')->sortable(),
                BadgeColumn::make('status')->label('Статус')
                    ->colors([
                        'gray'    => 'pending',
                        'warning' => 'processing',
                        'success' => 'ready',
                        'primary' => 'completed',
                        'danger'  => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'processing' => 'Готовится',
                        'ready'      => 'Готов',
                        'completed'  => 'Завершён',
                        'cancelled'  => 'Отменён',
                        default      => $state,
                    }),
                TextColumn::make('total_amount')->label('Сумма')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 0, ',', ' ') . ' ₽')->sortable(),
                BadgeColumn::make('payment_status')->label('Оплата')
                    ->colors(['danger' => 'unpaid', 'success' => 'paid']),
                TextColumn::make('delivery_type')->label('Тип доставки')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'delivery' => 'Доставка',
                        'table'    => 'Столик',
                        default    => $state,
                    }),
                TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')
                    ->options(['pending' => 'Ожидание', 'processing' => 'Готовится', 'ready' => 'Готов', 'completed' => 'Завершён', 'cancelled' => 'Отменён']),
                SelectFilter::make('payment_status')->label('Оплата')
                    ->options(['unpaid' => 'Не оплачен', 'paid' => 'Оплачен']),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc')->striped();
    }
}
