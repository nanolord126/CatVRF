<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources;

use App\Domains\Beauty\Models\Appointment;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePickerField;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

final class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $slug = 'marketplace/beauty/bookings';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Beauty';

    protected static ?string $navigationLabel = 'Appointments';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Appointment Details')->schema([
                Select::make('user_id')->relationship('user', 'name')->required(),
                Select::make('salon_id')->relationship('salon', 'name')->required(),
                Select::make('service_id')->relationship('service', 'name')->required(),
                Select::make('master_id')->relationship('master', 'full_name')->required(),
            ])->columns(2),

            Section::make('DateTime & Info')->schema([
                TextInput::make('appointment_date')->required()->type('date'),
                TextInput::make('appointment_time')->required()->type('time'),
                TextInput::make('price')->numeric()->step(0.01),
                TextInput::make('notes')->columnSpanFull(),
            ]),

            Section::make('Status')->schema([
                Select::make('status')->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                    'no-show' => 'No Show',
                ])->default('pending'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('user.name')->searchable(),
            TextColumn::make('salon.name')->searchable(),
            TextColumn::make('service.name')->searchable(),
            TextColumn::make('master.full_name')->searchable(),
            TextColumn::make('appointment_date')->date(),
            TextColumn::make('appointment_time')->time(),
            TextColumn::make('price')->numeric(),
            BadgeColumn::make('status')->colors([
                'pending' => 'warning',
                'confirmed' => 'info',
                'completed' => 'success',
                'cancelled' => 'danger',
            ]),
            TextColumn::make('created_at')->dateTime(),
        ])->filters([])->actions([])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => (new class extends ListRecords {
                protected static string $resource = AppointmentResource::class;
            })::route('/'),
            'create' => (new class extends CreateRecord {
                protected static string $resource = AppointmentResource::class;
            })::route('/create'),
            'edit' => (new class extends EditRecord {
                protected static string $resource = AppointmentResource::class;
            })::route('/{record}/edit'),
        ];
    }
}
