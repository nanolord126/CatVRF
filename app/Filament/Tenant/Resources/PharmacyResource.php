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

final class PharmacyResource extends Resource
{

    protected static ?string $model = Medicine::class;

        protected static ?string $navigationIcon = 'heroicon-o-heart';

        protected static ?string $navigationGroup = 'Medical';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Основная информация')
                        ->description('Базовые сведения об объекте')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('mnn')
                        ->required()
                        ->maxLength(255),
                                    Select::make('category')
                        ->required()
                        ->searchable(),
                                    TextInput::make('price')
                        ->required()
                        ->maxLength(255),
                                    TextInput::make('stock')
                        ->required()
                        ->maxLength(255),
                                    Toggle::make('requires_prescription')
                        ->required(),
                                    DatePicker::make('expiry_date')
                        ->required(),
                                ]),
                        ]),

                    Section::make('Дополнительно')
                        ->description('Расширенные параметры')
                        ->collapsed()
                        ->schema([
                            Grid::make(2)
                                ->schema([]),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListPharmacy::route('/'),
                'create' => Pages\CreatePharmacy::route('/create'),
                'edit' => Pages\EditPharmacy::route('/{record}/edit'),
                'view' => Pages\ViewPharmacy::route('/{record}'),
            ];
        }
}
