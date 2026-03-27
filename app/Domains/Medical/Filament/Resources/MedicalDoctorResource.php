<?php

declare(strict_types=1);


namespace App\Domains\Medical\Filament\Resources;

use App\Domains\Medical\Models\MedicalDoctor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final /**
 * MedicalDoctorResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MedicalDoctorResource extends Resource
{
    protected static ?string $model = MedicalDoctor::class;

    protected static ?string $navigationGroup = 'Medical';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('clinic_id')->relationship('clinic', 'name')->required(),
            TextInput::make('full_name')->required(),
            TextInput::make('specialization')->required(),
            TextInput::make('experience_years')->numeric(),
            TextInput::make('license_number')->unique(),
            RichEditor::make('bio')->columnSpanFull(),
            TextInput::make('consultation_price')->numeric()->step(0.01),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('full_name')->searchable(),
            TextColumn::make('clinic.name'),
            TextColumn::make('specialization'),
            TextColumn::make('experience_years'),
            TextColumn::make('consultation_price')->numeric()->sortable(),
            TextColumn::make('rating')->numeric()->sortable(),
        ])->filters([])->actions([])->bulkActions([]);
    }
}
