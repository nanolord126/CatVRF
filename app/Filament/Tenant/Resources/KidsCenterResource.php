<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class KidsCenterResource extends Resource
{


    protected static ?string $model = KidsCenter::class;

        protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
        protected static ?string $navigationGroup = 'Baby & Kids';
        protected static ?string $tenantOwnershipRelationshipName = 'tenant';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make('Location Details')
                                ->description('Physical center identity and address.')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('e.g. Wonderland Playground'),
                                    Forms\Components\Select::make('store_id')
                                        ->label('Affiliated Store')
                                        ->required()
                                        ->options(fn() => KidsStore::pluck('name', 'id'))
                                        ->searchable(),
                                    Forms\Components\Select::make('center_type')
                                        ->label('Facility Type')
                                        ->required()
                                        ->options([
                                            'playground' => 'Playground',
                                            'education' => 'Education Center',
                                            'club' => 'Kids Club',
                                            'day_care' => 'Day Care',
                                        ])
                                        ->default('playground'),
                                    Forms\Components\TextInput::make('address')
                                        ->label('Physical Street Address')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('geo_point')
                                        ->label('Coordinates (Lat/Lon)')
                                        ->placeholder('55.7558, 37.6173'),
                                ])->columns(2),

                            Forms\Components\Section::make('Operations & Capacity')
                                ->schema([
                                    Forms\Components\TextInput::make('capacity_limit')
                                        ->label('Max Children Capacity')
                                        ->required()
                                        ->numeric()
                                        ->default(20),
                                    Forms\Components\TextInput::make('hourly_rate')
                                        ->label('Entry / Hourly Fee (Kopecks)')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('RUB kop'),
                                    Forms\Components\Toggle::make('is_safety_verified')
                                        ->label('Official Safety Verification')
                                        ->required()
                                        ->onColor('success')
                                        ->offColor('danger'),
                                ])->columns(3),

                            Forms\Components\Section::make('Schedule')
                                ->description('Opening and closing hours.')
                                ->schema([
                                    Forms\Components\KeyValue::make('schedule_hours')
                                        ->label('Weekly Hours')
                                        ->keyLabel('Day')
                                        ->valueLabel('Opening Range (e.g. 09-21)')
                                        ->default([
                                            'monday' => '09:00 - 21:00',
                                            'saturday' => '10:00 - 18:00',
                                        ]),
                                ]),
                        ])->columnSpan(['lg' => 2]),

                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Section::make('Facilities & Tags')
                                ->schema([
                                    Forms\Components\CheckboxList::make('facility_details')
                                        ->label('Active Facilities')
                                        ->options([
                                            'pool' => 'Ball Pool',
                                            'cafe' => 'Parent Café',
                                            'lockers' => 'Safety Lockers',
                                            'cameras' => 'CCTV Monitoring',
                                            'parking' => 'Stroller Parking',
                                        ])
                                        ->columns(2),
                                    Forms\Components\TagsInput::make('tags')
                                        ->label('Discovery Tags')
                                        ->placeholder('e.g. soft-floor, eco-friendly'),
                                ]),

                            Forms\Components\Section::make('Audit Trace')
                                ->schema([
                                    Forms\Components\TextInput::make('uuid')
                                        ->label('Backend UUID')
                                        ->disabled()
                                        ->dehydrated(false),
                                    Forms\Components\TextInput::make('correlation_id')
                                        ->label('Trace Correlation ID')
                                        ->disabled()
                                        ->dehydrated(false),
                                    Forms\Components\DateTimePicker::make('created_at')
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                        ])->columnSpan(['lg' => 1]),
                ])->columns(3);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListKidsCenter::route('/'),
                'create' => Pages\CreateKidsCenter::route('/create'),
                'edit' => Pages\EditKidsCenter::route('/{record}/edit'),
                'view' => Pages\ViewKidsCenter::route('/{record}'),
            ];
        }
}
