<?php
namespace Modules\Advertising\Filament\Resources;
use Modules\Advertising\Models\Creative;
use Filament\{Forms, Tables, Resources\Resource};
use Filament\Forms\Components\{TextInput, Textarea};
use Filament\Tables\Columns\TextColumn;

class CreativeResource extends Resource {
    protected static ?string $model = Creative::class;
    protected static ?string $navigationGroup = 'Маркетинг и Реклама';

    public static function form(Forms\Form $form): Forms\Form {
        return $form->schema([
            TextInput::make('title')->required(),
            Textarea::make('content')->required(),
            TextInput::make('link')->url(),
            TextInput::make('erid')->disabled()->hint('ОРД Токен'),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table {
        return $table->columns([
            TextColumn::make('title')->searchable(),
            TextColumn::make('type')->badge(),
            TextColumn::make('erid')->label('Токен ОРД'),
            TextColumn::make('labeled_link')->label('Пром. Ссылка'),
        ]);
    }
}
