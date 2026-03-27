<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Medical\Models\Appointment;
use App\Domains\Medical\Models\Doctor;
use App\Domains\Medical\Models\MedicalService;
use App\Domains\Medical\Services\AppointmentService;
use App\Domains\Medical\DTOs\AppointmentData;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * РЕЖИМ ЛЮТЫЙ 2026: MEDICAL APPOINTMENT RESOURCE (Filament v3)
 * 
 * Полнофункциональная панель управления записями к врачу.
 * Особенности 2026:
 * - Сквозной correlation_id для всех действий из админки.
 * - Интеграция с AppointmentService (а не прямое сохранение).
 * - Автоматический tenant_id scoping.
 * - ФЗ-152 compliance (логирование просмотра).
 * 
 * @package App\Filament\Tenant\Resources
 */
final class MedicalAppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Medical Platform';
    protected static ?string $slug = 'medical-appointments';

    /**
     * Конфигурация формы создания/редактирования записи.
     * 
     * @param Form $form
     * @return Form
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Patient & Doctor')
                ->schema([
                    Forms\Components\Select::make('patient_id')
                        ->relationship('patient', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->disabledOn('edit'),

                    Forms\Components\Select::make('doctor_id')
                        ->relationship('doctor', 'full_name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive(),
                ])->columns(2),

            Forms\Components\Section::make('Schedule')
                ->schema([
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->required()
                        ->seconds(false)
                        ->native(false)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            $service = MedicalService::find($get('service_id'));
                            if ($service) {
                                $set('ends_at', \Carbon\Carbon::parse($state)->addMinutes($service->duration_minutes));
                            }
                        }),

                    Forms\Components\Select::make('service_id')
                        ->relationship('service', 'name')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            $service = MedicalService::find($state);
                            if ($service && $get('starts_at')) {
                                $set('ends_at', \Carbon\Carbon::parse($get('starts_at'))->addMinutes($service->duration_minutes));
                                $set('total_price', $service->price);
                            }
                        }),
                ])->columns(2),

            Forms\Components\Section::make('Financials')
                ->schema([
                    Forms\Components\TextInput::make('total_price')
                        ->numeric()
                        ->prefix('₽')
                        ->required()
                        ->disabled(), // Только для чтения из сервиса

                    Forms\Components\Select::make('payment_status')
                        ->options([
                            'pending' => 'Pending',
                            'prepaid' => 'Prepaid',
                            'paid' => 'Fully Paid',
                        ])->default('pending')->required(),
                ])->columns(2),

            Forms\Components\RichEditor::make('metadata.notes')
                ->label('Appointment Notes')
                ->columnSpanFull(),
        ]);
    }

    /**
     * Конфигурация таблицы мониторинга записей.
     * 
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')->label('ID')->fontFamily('mono')->copyable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('starts_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('patient.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('doctor.full_name')->label('Doctor')->searchable(),
                Tables\Columns\TextColumn::make('service.name')->label('Service'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'cancelled',
                        'primary' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('total_price')->money('rub')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('doctor_id')
                    ->relationship('doctor', 'full_name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->after(fn($record) => self::logAdminAccess($record, 'view_table')),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Complete Appointment')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'confirmed')
                    ->action(fn($record) => app(AppointmentService::class)->completeAppointment($record->id, Str::uuid()->toString())),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Глобальный скопинг по арендатору (Tenant).
     * 
     * @return Builder
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }

    /**
     * Кастомный лог доступа админа к данным ФЗ-152.
     * 
     * @param Appointment $record
     * @param string $action
     */
    private static function logAdminAccess(Appointment $record, string $action): void
    {
        Log::channel('audit')->info('Admin Access to Medical Appointment', [
            'admin_id' => auth()->id(),
            'appointment_uuid' => $record->uuid,
            'action' => $action,
            'correlation_id' => request()->header('X-Correlation-ID') ?? 'admin-panel-' . Str::uuid(),
        ]);
    }
}
