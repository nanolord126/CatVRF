<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TagsInput;

final class ServiceResource extends Resource
{

    protected static ?string $model = Service::class;

        protected static ?string $navigationIcon = 'heroicon-o-scissors';

        protected static ?string $navigationLabel = 'Услуги';

        protected static ?string $navigationGroup = 'Beauty';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Select::make('salon_id')
                    ->relationship('salon', 'name')
                    ->label('Салон'),
                Forms\Components\Select::make('master_id')
                    ->relationship('master', 'full_name')
                    ->label('Мастер'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Название услуги'),
                Forms\Components\Textarea::make('description')
                    ->maxLength(1000)
                    ->label('Описание'),
                Forms\Components\TextInput::make('duration_minutes')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->label('Длительность (мин)'),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->label('Цена'),
                Forms\Components\KeyValue::make('consumables_json')
                    ->label('Расходники'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),
            ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListService::route('/'),
                'create' => Pages\CreateService::route('/create'),
                'edit' => Pages\EditService::route('/{record}/edit'),
                'view' => Pages\ViewService::route('/{record}'),
            ];
        }
}
