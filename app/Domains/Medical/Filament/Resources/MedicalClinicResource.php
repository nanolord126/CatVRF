declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Medical\Filament\Resources;

use App\Domains\Medical\Models\MedicalClinic;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

final /**
 * MedicalClinicResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MedicalClinicResource extends Resource
{
    protected static ?string $model = MedicalClinic::class;

    protected static ?string $navigationGroup = 'Medical';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            RichEditor::make('description')->columnSpanFull(),
            TextInput::make('address')->required(),
            TextInput::make('phone')->required(),
            TextInput::make('email')->required()->email(),
            TextInput::make('license_number')->unique(),
            Toggle::make('is_verified')->default(false),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('owner.name'),
            TextColumn::make('doctor_count')->numeric(),
            TextColumn::make('rating')->numeric()->sortable(),
            IconColumn::make('is_verified')->boolean(),
            IconColumn::make('is_active')->boolean(),
        ])->filters([])->actions([])->bulkActions([]);
    }
}
