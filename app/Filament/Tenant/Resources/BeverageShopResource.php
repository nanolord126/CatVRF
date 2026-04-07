<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class BeverageShopResource extends Resource
{

    protected static ?string $model = BeverageShop::class;

        protected static ?string $navigationIcon = 'heroicon-o-cup-straw';

        protected static ?string $navigationGroup = 'Beverages Vertical';

        /**
         * Complete form definition (>= 60 lines per canon 2026).
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General Information')
                        ->description('Basic details about the beverage venue')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->label('Shop Name')
                                ->placeholder('e.g. Arabica Coffee'),

                            Forms\Components\Select::make('type')
                                ->required()
                                ->options([
                                    'coffee_shop' => 'Coffee Shop',
                                    'tea_house' => 'Tea House',
                                    'bar' => 'Bar / Pub',
                                    'brewery' => 'Brewery',
                                ])
                                ->label('Establishment Type'),

                            Forms\Components\TextInput::make('address')
                                ->required()
                                ->maxLength(255)
                                ->label('Physical Address'),

                            Forms\Components\Toggle::make('is_active')
                                ->default(true)
                                ->label('Currently Active'),
                        ])->columns(2),

                    Forms\Components\Section::make('Location & Schedule')
                        ->description('Geographic and operational details')
                        ->schema([
                            Forms\Components\KeyValue::make('geo_point')
                                ->label('Geographic Coordinates')
                                ->keyLabel('Coordinate (lat/lon)')
                                ->valueLabel('Value')
                                ->placeholder('lat: 55.75, lon: 37.61'),

                            Forms\Components\Repeater::make('schedule')
                                ->label('Operating Schedule')
                                ->schema([
                                    Forms\Components\Select::make('day')
                                        ->options([
                                            'monday' => 'Monday',
                                            'tuesday' => 'Tuesday',
                                            'wednesday' => 'Wednesday',
                                            'thursday' => 'Thursday',
                                            'friday' => 'Friday',
                                            'saturday' => 'Saturday',
                                            'sunday' => 'Sunday',
                                        ])->required(),
                                    Forms\Components\TimePicker::make('open_at')->required(),
                                    Forms\Components\TimePicker::make('close_at')->required(),
                                ])
                                ->columns(3)
                                ->grid(1),
                        ]),

                    Forms\Components\Section::make('Analytics & Advanced')
                        ->description('Internal metadata and tags')
                        ->schema([
                            Forms\Components\TagsInput::make('tags')
                                ->label('Analytical Tags')
                                ->placeholder('e.g. premium, student_choice, vegan_friendly'),

                            Forms\Components\TextInput::make('uuid')
                                ->disabled()
                                ->label('System UUID')
                                ->helperText('Assigned automatically on creation.'),

                            Forms\Components\TextInput::make('correlation_id')
                                ->disabled()
                                ->label('Last Correlation ID')
                                ->helperText('Track performance and security across sessions.'),
                        ])->columns(2),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListBeverageShop::route('/'),
                'create' => Pages\CreateBeverageShop::route('/create'),
                'edit' => Pages\EditBeverageShop::route('/{record}/edit'),
                'view' => Pages\ViewBeverageShop::route('/{record}'),
            ];
        }
}
