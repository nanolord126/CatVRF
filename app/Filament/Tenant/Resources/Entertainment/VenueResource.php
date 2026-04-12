<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class VenueResource extends Resource
{

    protected static ?string $model = Venue::class;

        protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

        protected static ?string $navigationGroup = 'Entertainment';

        protected static ?string $tenantOwnershipRelationshipName = 'tenant';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General Information')
                        ->description('Basic details about the entertainment venue')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('uuid', (string) Str::uuid()) : null),
                            Forms\Components\Select::make('type')
                                ->options([
                                    'cinema' => 'Cinema',
                                    'theater' => 'Theater',
                                    'concert_hall' => 'Concert Hall',
                                    'club' => 'Night Club',
                                    'quest' => 'Quest Room',
                                    'bowling' => 'Bowling Alley',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('address')
                                ->required()
                                ->maxLength(500),
                            Forms\Components\TextInput::make('uuid')
                                ->disabled()
                                ->dehydrated()
                                ->default(fn () => (string) Str::uuid()),
                        ])->columns(2),

                    Forms\Components\Section::make('Configuration')
                        ->schema([
                            Forms\Components\KeyValue::make('schedule_json')
                                ->label('Opening Hours')
                                ->keyLabel('Day')
                                ->valueLabel('Hours'),
                            Forms\Components\Toggle::make('is_active')
                                ->default(true),
                            Forms\Components\TagsInput::make('tags')
                                ->placeholder('Add tags (e.g. VIP, Dolby, Bar)'),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\BadgeColumn::make('type')
                        ->colors([
                            'primary' => 'cinema',
                            'success' => 'theater',
                            'warning' => 'concert_hall',
                            'danger' => 'club',
                            'info' => 'quest',
                            'secondary' => 'bowling',
                        ]),
                    Tables\Columns\TextColumn::make('rating')
                        ->numeric(1)
                        ->sortable(),
                    Tables\Columns\IconColumn::make('is_active')
                        ->boolean(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('type'),
                    Tables\Filters\TernaryFilter::make('is_active'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListVenues::route('/'),
                'create' => Pages\CreateVenue::route('/create'),
                'edit' => Pages\EditVenue::route('/{record}/edit'),
            ];
        }
}
