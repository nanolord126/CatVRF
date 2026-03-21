<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Domains\Events\Models\Event;

class EventsResource extends Resource
{
    protected static ?string $model = Event::class;
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
