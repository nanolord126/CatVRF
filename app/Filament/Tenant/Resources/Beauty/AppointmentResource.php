<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Application\B2B\DTOs\CancelAppointmentDTO;
use App\Domains\Beauty\Application\B2B\DTOs\CompleteAppointmentDTO;
use App\Domains\Beauty\Application\B2B\DTOs\ConfirmAppointmentDTO;
use App\Domains\Beauty\Application\B2B\UseCases\CancelAppointmentUseCase;
use App\Domains\Beauty\Application\B2B\UseCases\CompleteAppointmentUseCase;
use App\Domains\Beauty\Application\B2B\UseCases\ConfirmAppointmentUseCase;
use App\Domains\Beauty\Domain\Enums\AppointmentStatus;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyAppointment;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyMaster;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautySalon;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Filament Resource: Записи (Beauty Appointments).
 *
 * Tenant-scoped через getEloquentQuery().
 * Сервисы резолвятся через app() — constructor injection в Resource не поддерживается.
 * Нет Facades, нет статических вызовов.
 *
 * @package App\Filament\Tenant\Resources\Beauty
 */
final class AppointmentResource extends Resource
{
    protected static ?string $model = BeautyAppointment::class;

    protected static ?string $navigationIcon   = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup  = 'Beauty';
    protected static ?string $navigationLabel  = 'Записи';
    protected static ?string $modelLabel       = 'Запись';
    protected static ?string $pluralModelLabel = 'Записи';
    protected static ?int    $navigationSort   = 40;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('uuid')
                ->default(fn () => Str::uuid()->toString()),
            Hidden::make('correlation_id')
                ->default(fn () => Str::uuid()->toString()),

            Section::make('Участники записи')
                ->columns(2)
                ->schema([
                    Select::make('salon_id')
                        ->label('Салон')
                        ->options(fn () => BeautySalon::query()
                            ->where('tenant_id', filament()->getTenant()?->id)
                            ->where('is_active', true)
                            ->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->live()
                        ->columnSpan(2),
                    Select::make('master_id')
                        ->label('Мастер')
                        ->options(function ($get) {
                            $salonId = $get('salon_id');
                            if (! $salonId) {
                                return [];
                            }
                            return BeautyMaster::query()
                                ->where('salon_id', $salonId)
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->searchable(),
                    Select::make('service_id')
                        ->label('Услуга')
                        ->options(function ($get) {
                            $salonId = $get('salon_id');
                            if (! $salonId) {
                                return [];
                            }
                            return BeautyService::query()
                                ->where('salon_id', $salonId)
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->searchable(),
                    TextInput::make('client_id')
                        ->label('ID клиента')
                        ->required()
                        ->numeric(),
                ]),

            Section::make('Время и стоимость')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('start_at')
                        ->label('Начало')
                        ->required()
                        ->seconds(false),
                    DateTimePicker::make('end_at')
                        ->label('Окончание')
                        ->seconds(false),
                    TextInput::make('price_cents')
                        ->label('Стоимость (в копейках)')
                        ->numeric()
                        ->required()
                        ->minValue(0),
                ]),

            Section::make('Статус')
                ->schema([
                    Select::make('status')
                        ->label('Статус')
                        ->options(fn () => collect(AppointmentStatus::cases())
                            ->mapWithKeys(fn (AppointmentStatus $s) => [$s->value => $s->label()]))
                        ->required()
                        ->disabled(fn (string $operation): bool => $operation === 'create')
                        ->default(AppointmentStatus::PENDING->value),
                    Textarea::make('cancellation_reason')
                        ->label('Причина отмены')
                        ->rows(2)
                        ->visible(fn ($get) => $get('status') === AppointmentStatus::CANCELLED->value),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 8) . '...')
                    ->searchable(),
                TextColumn::make('salon.name')
                    ->label('Салон')
                    ->searchable(),
                TextColumn::make('master.name')
                    ->label('Мастер')
                    ->searchable(),
                TextColumn::make('service.name')
                    ->label('Услуга'),
                TextColumn::make('start_at')
                    ->label('Начало')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('price_cents')
                    ->label('Стоимость')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, '.', ' ') . ' ₽')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AppointmentStatus::from($state)->label())
                    ->color(fn (string $state): string => AppointmentStatus::from($state)->color())
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(fn () => collect(AppointmentStatus::cases())
                        ->mapWithKeys(fn (AppointmentStatus $s) => [$s->value => $s->label()])),
                SelectFilter::make('salon_id')
                    ->label('Салон')
                    ->options(fn () => BeautySalon::query()
                        ->where('tenant_id', filament()->getTenant()?->id)
                        ->pluck('name', 'id')),
            ])
            ->actions([
                Action::make('confirm')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Подтвердить запись клиента?')
                    ->visible(fn (BeautyAppointment $record): bool => $record->status === AppointmentStatus::PENDING->value)
                    ->action(function (BeautyAppointment $record, ConfirmAppointmentUseCase $useCase): void {
                        $correlationId = Str::uuid()->toString();
                        $logger = app(LoggerInterface::class);

                        try {
                            $useCase->handle(new ConfirmAppointmentDTO(
                                tenantId: filament()->getTenant()?->id ?? 0,
                                confirmedByUserId: filament()->auth()->id() ?? 0,
                                appointmentUuid: $record->uuid,
                                correlationId: $correlationId,
                            ));

                            Notification::make()
                                ->title('Запись подтверждена')
                                ->success()
                                ->send();

                            $logger->info('Beauty: запись подтверждена', [
                                'appointment_uuid' => $record->uuid,
                                'correlation_id'   => $correlationId,
                            ]);
                        } catch (\DomainException $e) {
                            $logger->error('Beauty: ошибка подтверждения записи', [
                                'appointment_uuid' => $record->uuid,
                                'error'            => $e->getMessage(),
                                'correlation_id'   => $correlationId,
                            ]);

                            Notification::make()
                                ->title('Ошибка: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('complete')
                    ->label('Завершить')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalDescription('Отметить запись как выполненную?')
                    ->visible(fn (BeautyAppointment $record): bool => $record->status === AppointmentStatus::CONFIRMED->value)
                    ->action(function (BeautyAppointment $record, CompleteAppointmentUseCase $useCase): void {
                        $correlationId = Str::uuid()->toString();
                        $logger = app(LoggerInterface::class);

                        try {
                            $useCase->handle(new CompleteAppointmentDTO(
                                tenantId: filament()->getTenant()?->id ?? 0,
                                completedByUserId: filament()->auth()->id() ?? 0,
                                appointmentUuid: $record->uuid,
                                correlationId: $correlationId,
                            ));

                            Notification::make()
                                ->title('Запись завершена')
                                ->success()
                                ->send();

                            $logger->info('Beauty: запись завершена', [
                                'appointment_uuid' => $record->uuid,
                                'correlation_id'   => $correlationId,
                            ]);
                        } catch (\DomainException $e) {
                            $logger->error('Beauty: ошибка завершения записи', [
                                'appointment_uuid' => $record->uuid,
                                'error'            => $e->getMessage(),
                                'correlation_id'   => $correlationId,
                            ]);

                            Notification::make()
                                ->title('Ошибка: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Отменить запись? Укажите причину.')
                    ->form([
                        Textarea::make('reason')
                            ->label('Причина отмены')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (BeautyAppointment $record): bool => ! in_array(
                        $record->status,
                        [AppointmentStatus::COMPLETED->value, AppointmentStatus::CANCELLED->value],
                        true
                    ))
                    ->action(function (BeautyAppointment $record, array $data, CancelAppointmentUseCase $useCase): void {
                        $correlationId = Str::uuid()->toString();
                        $logger = app(LoggerInterface::class);

                        try {
                            $useCase->handle(new CancelAppointmentDTO(
                                tenantId: filament()->getTenant()?->id ?? 0,
                                cancelledByUserId: filament()->auth()->id() ?? 0,
                                appointmentUuid: $record->uuid,
                                reason: $data['reason'],
                                correlationId: $correlationId,
                            ));

                            Notification::make()
                                ->title('Запись отменена')
                                ->warning()
                                ->send();

                            $logger->info('Beauty: запись отменена', [
                                'appointment_uuid' => $record->uuid,
                                'reason'           => $data['reason'],
                                'correlation_id'   => $correlationId,
                            ]);
                        } catch (\DomainException $e) {
                            $logger->error('Beauty: ошибка отмены записи', [
                                'appointment_uuid' => $record->uuid,
                                'error'            => $e->getMessage(),
                                'correlation_id'   => $correlationId,
                            ]);

                            Notification::make()
                                ->title('Ошибка: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('salon', function (Builder $query) {
                $query->where('tenant_id', filament()->getTenant()?->id);
            })
            ->with(['salon', 'master', 'service']);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit'   => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}

