declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Domains\Events\Models\Event;

/**
 * EventsResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EventsResource extends Resource
{
    protected static ?string $model = $this->event->class;
    protected static ?string $navigationIcon = "heroicon-o-calendar";

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }
}
