<?php

declare(strict_types=1);


namespace App\Domains\Medical\Filament\Resources;

use App\Domains\Medical\Models\MedicalTestOrder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

final /**
 * MedicalTestOrderResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MedicalTestOrderResource extends Resource
{
    protected static ?string $model = MedicalTestOrder::class;

    protected static ?string $navigationGroup = 'Medical';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('appointment_id')->relationship('appointment', 'appointment_number')->required(),
            Select::make('patient_id')->relationship('patient', 'name')->required(),
            Select::make('clinic_id')->relationship('clinic', 'name')->required(),
            TextInput::make('total_amount')->numeric()->step(0.01)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('test_order_number')->searchable(),
            TextColumn::make('patient.name'),
            TextColumn::make('clinic.name'),
            TextColumn::make('total_amount')->numeric()->sortable(),
            TextColumn::make('commission_amount')->numeric()->sortable(),
            BadgeColumn::make('status'),
            TextColumn::make('ordered_at')->sortable(),
        ])->filters([])->actions([])->bulkActions([]);
    }
}
