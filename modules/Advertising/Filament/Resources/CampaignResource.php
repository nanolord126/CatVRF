declare(strict_types=1);

<?php
namespace Modules\Advertising\Filament\Resources;
use Modules\Advertising\Models\Campaign;
use Filament\{Forms, Tables, Resources\Resource};
use Filament\Forms\Components\{TextInput, DatePicker, Select};
use Filament\Tables\Columns\{TextColumn, BooleanColumn};

/**
 * CampaignResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CampaignResource extends Resource {
    protected static ?string $model = Campaign::class;
    protected static ?string $navigationGroup = 'Маркетинг и Реклама';

    public static function form(Forms\Form $form): Forms\Form {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('budget')->numeric()->prefix('₽'),
            Select::make('vertical')->options(['hotel'=>'Отели', 'food'=>'Еда']),
            DatePicker::make('start_date'), DatePicker::make('end_date')
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('budget')->money('RUB'),
            TextColumn::make('vertical')->badge(),
            BooleanColumn::make('is_active'),
        ]);
    }
}
