declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FarmDirectResource;

use App\Domains\FarmDirect\Models\Farm;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;

final /**
 * FarmDirectResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FarmDirectResource extends Resource
{
    protected static ?string $model = Farm::class;
    public static function form(Form $f): Form
    {
        return $f->schema([Section::make('Info')->schema([TextInput::make('name')->required()])]);
    }
    public static function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('name')->sortable()->searchable()]);
    }
}
