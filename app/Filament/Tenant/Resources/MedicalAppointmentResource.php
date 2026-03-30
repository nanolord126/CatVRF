<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalAppointmentResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListMedicalAppointment::route('/'),
                'create' => Pages\\CreateMedicalAppointment::route('/create'),
                'edit' => Pages\\EditMedicalAppointment::route('/{record}/edit'),
                'view' => Pages\\ViewMedicalAppointment::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListMedicalAppointment::route('/'),
                'create' => Pages\\CreateMedicalAppointment::route('/create'),
                'edit' => Pages\\EditMedicalAppointment::route('/{record}/edit'),
                'view' => Pages\\ViewMedicalAppointment::route('/{record}'),
            ];
        }
}
