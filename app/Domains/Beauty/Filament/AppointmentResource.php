<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppointmentResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Section, Select, DateTimeInput};
    use Filament\Tables\Columns\{TextColumn, BadgeColumn};
    use Filament\Tables\Actions\{Action, DeleteAction, EditAction};
    use Filament\Tables\Filters\{Filter, TrashedFilter, SelectFilter};

    /**
     * Filament Resource для записей на услуги.
     * Production 2026.
     */
    final class AppointmentResource extends Resource
    {
        protected static ?string $model = Appointment::class;

        protected static ?string $navigationIcon = 'heroicon-o-calendar';

        protected static ?string $navigationLabel = 'Записи';

        protected static ?string $pluralModelLabel = 'Записи на услуги';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Запись')
                    ->schema([
                        Select::make('salon_id')
                            ->label('Салон')
                            ->relationship('salon', 'name')
                            ->required(),
                        Select::make('master_id')
                            ->label('Мастер')
                            ->relationship('master', 'full_name')
                            ->required(),
                        Select::make('service_id')
                            ->label('Услуга')
                            ->relationship('service', 'name')
                            ->required(),
                        Select::make('client_id')
                            ->label('Клиент')
                            ->relationship('client', 'name')
                            ->required(),
                        DateTimeInput::make('datetime_start')
                            ->label('Дата и время начала')
                            ->required(),
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидание',
                                'confirmed' => 'Подтверждена',
                                'completed' => 'Завершена',
                                'cancelled' => 'Отменена',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('salon.name')
                        ->label('Салон')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('master.full_name')
                        ->label('Мастер')
                        ->searchable(),
                    TextColumn::make('service.name')
                        ->label('Услуга')
                        ->searchable(),
                    TextColumn::make('datetime_start')
                        ->label('Дата/время')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),
                    BadgeColumn::make('status')
                        ->label('Статус')
                        ->getStateUsing(fn ($record) => match ($record->status) {
                            'pending' => 'Ожидание',
                            'confirmed' => 'Подтверждена',
                            'completed' => 'Завершена',
                            'cancelled' => 'Отменена',
                            default => $record->status,
                        }),
                ])
                ->filters([
                    TrashedFilter::make(),
                    SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'pending' => 'Ожидание',
                            'confirmed' => 'Подтверждена',
                            'completed' => 'Завершена',
                            'cancelled' => 'Отменена',
                        ]),
                ])
                ->actions([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->bulkActions([
                    // Bulk actions here
                ]);
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => ListRecords::class,
                'create' => CreateRecord::class,
                'edit' => EditRecord::class,
                'view' => ViewRecord::class,
            ];
        }
}
