<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class OfficeCateringResource extends Resource
{

    protected static ?string $model = CorporateOrder::class;

        protected static ?string $navigationIcon = 'heroicon-o-briefcase';

        protected static ?string $navigationGroup = 'Catering';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Основная информация')
                        ->description('Базовые сведения об объекте')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                                    TextInput::make('company_name')
                        ->required()
                        ->maxLength(255),
                                    DatePicker::make('order_date')
                        ->required(),
                                    TextInput::make('employee_count')
                        ->required()
                        ->maxLength(255),
                                    Select::make('menu_type')
                        ->required()
                        ->searchable(),
                                    TextInput::make('total_price')
                        ->required()
                        ->maxLength(255),
                                    Select::make('status')
                        ->required()
                        ->searchable(),
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
                'index' => Pages\ListOfficeCatering::route('/'),
                'create' => Pages\CreateOfficeCatering::route('/create'),
                'edit' => Pages\EditOfficeCatering::route('/{record}/edit'),
                'view' => Pages\ViewOfficeCatering::route('/{record}'),
            ];
        }
}
