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

final class PhotoStudioResource extends Resource
{

    protected static ?string $model = PhotoStudio::class;

        protected static ?string $navigationIcon = 'heroicon-o-camera';

        protected static ?string $navigationGroup = 'Photography';

        protected static ?string $tenantOwnershipRelationshipName = 'tenant';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация Студии')
                        ->description('Укажите базовые параметры фотостудии и её оснащение.')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Название студии')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Напр. Loft Studio 2026')
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('description')
                                ->label('Описание')
                                ->rows(4)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('address')
                                ->label('Физический адрес')
                                ->required()
                                ->placeholder('Город, улица, дом, этаж')
                                ->columnSpanFull(),

                            Forms\Components\Group::make([
                                Forms\Components\TextInput::make('rating')
                                    ->label('Рейтинг')
                                    ->numeric()
                                    ->default(5.0)
                                    ->step(0.1)
                                    ->disabled(),

                                Forms\Components\TextInput::make('review_count')
                                    ->label('Кол-во отзывов')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),
                            ])->columns(2),
                        ])->columns(2),

                    Forms\Components\Section::make('Параметры и Оборудование')
                        ->schema([
                            Forms\Components\KeyValue::make('amenities')
                                ->label('Удобства и Оборудование')
                                ->keyLabel('Название (напр. Импульсный свет)')
                                ->valueLabel('Описание/Кол-во')
                                ->columnSpanFull(),

                            Forms\Components\JsonEditor::make('schedule_json')
                                ->label('График работы (JSON)')
                                ->helperText('Формат: {"monday": {"open": "09:00", "close": "22:00"}, ...}')
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Section::make('Системные данные')
                        ->collapsed()
                        ->schema([
                            Forms\Components\Toggle::make('is_verified')
                                ->label('Верифицирована')
                                ->default(false),

                            Forms\Components\TextInput::make('uuid')
                                ->label('UUID')
                                ->default(fn () => (string) Str::uuid())
                                ->disabled()
                                ->dehydrated(),

                            Forms\Components\TextInput::make('correlation_id')
                                ->label('Correlation ID')
                                ->default(fn () => (string) Str::uuid())
                                ->disabled()
                                ->dehydrated(),

                            Forms\Components\TagsInput::make('tags')
                                ->label('Теги (для AI)')
                                ->placeholder('лофт, неон, аквазона'),
                        ])->columns(2),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListPhotoStudio::route('/'),
                'create' => Pages\CreatePhotoStudio::route('/create'),
                'edit' => Pages\EditPhotoStudio::route('/{record}/edit'),
                'view' => Pages\ViewPhotoStudio::route('/{record}'),
            ];
        }
}
