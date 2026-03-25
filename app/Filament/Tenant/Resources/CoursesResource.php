declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Domains\Courses\Models\B2BCoursesOrder;
use Illuminate\Database\Eloquent\Builder;

/**
 * CoursesResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CoursesResource extends Resource
{
    protected static ?string $model = B2BCoursesOrder::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            // Add your schema here
            Forms\Components\TextInput::make('name')->required(),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])
        ->filters([
            //
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => \Filament\Resources\Pages\ListRecords::route('/'),
        ];
    }
}