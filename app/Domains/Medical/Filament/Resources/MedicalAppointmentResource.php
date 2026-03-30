<?php declare(strict_types=1);

namespace App\Domains\Medical\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalAppointmentResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = MedicalAppointment::class;

        protected static ?string $navigationGroup = 'Medical';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Select::make('clinic_id')->relationship('clinic', 'name')->required(),
                Select::make('doctor_id')->relationship('doctor', 'full_name')->required(),
                Select::make('patient_id')->relationship('patient', 'name')->required(),
                Select::make('service_id')->relationship('service', 'name')->required(),
                DateTimePickerInput::make('scheduled_at')->required(),
                TextInput::make('price')->numeric()->required(),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('appointment_number')->searchable(),
                TextColumn::make('doctor.full_name'),
                TextColumn::make('patient.name'),
                TextColumn::make('price')->numeric()->sortable(),
                BadgeColumn::make('status'),
                TextColumn::make('scheduled_at')->sortable(),
                TextColumn::make('completed_at')->sortable(),
            ])->filters([])->actions([])->bulkActions([]);
        }
}
