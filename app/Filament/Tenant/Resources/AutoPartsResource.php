declare(strict_types=1);

<?php declare(strict_types=1); namespace App\Filament\Tenant\Resources\AutoParts; use App\Domains\AutoParts\Models\AutoPart; use App\Filament\Tenant\Resources\AutoPartsResource\Pages; use Filament\Forms\Components\TextInput; use Filament\Forms\Components\Select; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables\Columns\TextColumn; use Filament\Tables\Table; final /**
 * AutoPartsResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class AutoPartsResource extends Resource { protected static ?string $model = AutoPart::class; protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver'; protected static ?string $navigationGroup = 'Auto'; public static function form(Form $form): Form { return $form->schema([TextInput::make('name')->required()->maxLength(255), TextInput::make('sku')->required()->unique()->maxLength(50), Select::make('part_type')->required()->options(['engine' => 'Engine', 'transmission' => 'Transmission', 'suspension' => 'Suspension', 'brake' => 'Brake', 'electrical' => 'Electrical', 'interior' => 'Interior', 'exterior' => 'Exterior']), TextInput::make('price')->numeric()->required(), TextInput::make('current_stock')->numeric()->required(), TextInput::make('rating')->numeric()->step(0.1), TextInput::make('review_count')->numeric(),]); } public static function table(Table $table): Table { return $table->columns([TextColumn::make('name')->sortable()->searchable(), TextColumn::make('sku')->sortable(), TextColumn::make('part_type'), TextColumn::make('price')->formatStateUsing(fn($state) => $state . ' ₽'), TextColumn::make('current_stock'), TextColumn::make('rating')->sortable(), ])->filters([])->actions([\Filament\Tables\Actions\EditAction::make(),])->bulkActions([\Filament\Tables\Actions\BulkActionGroup::make([\Filament\Tables\Actions\DeleteBulkAction::make(),]),]); } public static function getPages(): array { return ['index' => Pages\ListAutoPartss::route('/'), 'create' => Pages\CreateAutoPartss::route('/create'), 'edit' => Pages\EditAutoPartss::route('/{record}/edit'),]; } }
