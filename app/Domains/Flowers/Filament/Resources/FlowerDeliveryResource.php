declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources;

use App\Domains\Flowers\Models\FlowerDelivery;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final /**
 * FlowerDeliveryResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FlowerDeliveryResource extends Resource
{
    protected static ?string $model = FlowerDelivery::class;
    protected static ?string $slug = 'flower-deliveries';
    protected static ?string $navigationGroup = 'Flowers';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')->options([
                'pending' => 'Ожидание',
                'in_transit' => 'В пути',
                'delivered' => 'Доставлено',
            ])->required(),
            Forms\Components\TextInput::make('latitude')->numeric(),
            Forms\Components\TextInput::make('longitude')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('status'),
            Tables\Columns\TextColumn::make('pickup_time')->dateTime(),
            Tables\Columns\TextColumn::make('delivery_time')->dateTime(),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status'),
        ])->actions([
            Tables\Actions\ViewAction::make(),
        ]);
    }
}
