<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\DentalAppointmentResource;
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

final class ListDentalAppointment extends ListRecords
{
    protected static string $resource = DentalAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Записать пациента')->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('scheduled_at')->label('Дата/время')
                    ->dateTime('d.m.Y H:i')->sortable()->weight('bold'),
                TextColumn::make('dentist_id')->label('Врач ID')->sortable(),
                TextColumn::make('client_id')->label('Пациент ID')->sortable(),
                BadgeColumn::make('status')->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'confirmed',
                        'success' => 'completed',
                        'danger'  => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'confirmed' => 'Подтверждён',
                        'completed' => 'Завершён',
                        'cancelled' => 'Отменён',
                        default     => $state,
                    }),
                TextColumn::make('total_price')->label('Стоимость')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 0, ',', ' ') . ' ₽' : '—')->sortable(),
                IconColumn::make('is_prepaid')->label('Предоплачено')->boolean(),
                TextColumn::make('correlation_id')->label('Corr. ID')->toggleable(isToggledHiddenByDefault: true)->limit(16),
                TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')
                    ->options(['pending' => 'Ожидание', 'confirmed' => 'Подтверждён', 'completed' => 'Завершён', 'cancelled' => 'Отменён']),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('scheduled_at', 'desc')->striped();
    }
}
