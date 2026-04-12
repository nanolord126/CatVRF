<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class StrPropertyResource extends Resource
{

    protected static ?string $model = StrProperty::class;
        protected static ?string $navigationIcon = 'heroicon-o-home-modern';
        protected static ?string $navigationGroup = 'ShortTerm Rentals';
        protected static ?string $label = 'Объект (Дом/ЖК)';
        protected static ?string $pluralLabel = 'Объекты';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Select::make('business_group_id')
                                ->label('Филиал (ИНН)')
                                ->options(BusinessGroup::all()->pluck('name', 'id'))
                                ->searchable(),
                            Forms\Components\Select::make('type')
                                ->options([
                                    'apartment' => 'Квартира',
                                    'studio' => 'Студия',
                                    'loft' => 'Лофт',
                                    'villa' => 'Вилла/Коттедж',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('city')
                                ->required(),
                            Forms\Components\TextInput::make('address')
                                ->required()
                                ->columnSpanFull(),
                        ])->columns(2),

                    Forms\Components\Section::make('Координаты и Статус')
                        ->schema([
                            Forms\Components\TextInput::make('lat')->numeric(),
                            Forms\Components\TextInput::make('lon')->numeric(),
                            Forms\Components\Toggle::make('is_active')->default(true),
                            Forms\Components\Toggle::make('is_verified')->default(false),
                        ])->columns(2),

                    Forms\Components\Section::make('Дополнительно')
                        ->schema([
                            Forms\Components\KeyValue::make('tags')->label('Теги аналитики'),
                            Forms\Components\JsonEditor::make('schedule_json')->label('Расписание и Правила'),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListStrProperty::route('/'),
                'create' => Pages\CreateStrProperty::route('/create'),
                'edit' => Pages\EditStrProperty::route('/{record}/edit'),
                'view' => Pages\ViewStrProperty::route('/{record}'),
            ];
        }
}
