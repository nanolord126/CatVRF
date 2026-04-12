<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class MusicStudioResource extends Resource
{

    protected static ?string $model = MusicStudio::class;

        protected static ?string $navigationIcon = 'heroicon-o-microphone';

        protected static ?string $navigationGroup = 'Music & Instruments';

        protected static ?string $modelLabel = 'Studio';

        protected static ?string $pluralModelLabel = 'Studios';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General Information')
                        ->description('Primary studio details and ownership')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g. Abbey Road Studio 1'),

                            Forms\Components\Select::make('store_id')
                                ->label('Managed by Store')
                                ->options(MusicStore::pluck('name', 'id'))
                                ->searchable()
                                ->required(),

                            Forms\Components\Textarea::make('description')
                                ->maxLength(65535)
                                ->columnSpanFull(),
                        ])->columns(2),

                    Forms\Components\Section::make('Technical Details')
                        ->description('Equipment and capabilities')
                        ->schema([
                            Forms\Components\TagsInput::make('equipment_list')
                                ->label('Equipment & Instruments')
                                ->placeholder('Add microphone, mixer, piano...')
                                ->suggestions([
                                    'SM58 Microphone', 'Neumann U87', 'SSL Console',
                                    'Steinway Grand Piano', 'Pioneer Nexus', 'Ampeg SVT'
                                ]),

                            Forms\Components\TextInput::make('capacity')
                                ->numeric()
                                ->default(2)
                                ->required()
                                ->label('Person Capacity'),

                            Forms\Components\Toggle::make('is_recording_studio')
                                ->label('Recording Studio')
                                ->default(true),

                            Forms\Components\Toggle::make('is_rehearsal_room')
                                ->label('Rehearsal Room')
                                ->default(true),
                        ])->columns(2),

                    Forms\Components\Section::make('Location & Pricing')
                        ->description('Geographic and commercial parameters')
                        ->schema([
                            Forms\Components\TextInput::make('address')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('hourly_rate')
                                ->label('Hourly Rate (in kopeks)')
                                ->numeric()
                                ->required()
                                ->default(150000)
                                ->suffix('коп/час'),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Visible in Marketplace')
                                ->default(true),

                            Forms\Components\KeyValue::make('tags')
                                ->label('Metadata Tags')
                                ->required(false),
                        ])->columns(2),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->description(fn (MusicStudio $record): string => Str::limit($record->description ?? '', 30)),

                    Tables\Columns\TextColumn::make('store.name')
                        ->label('Store')
                        ->sortable()
                        ->searchable(),

                    Tables\Columns\TextColumn::make('hourly_rate')
                        ->money('RUB', divisor: 100)
                        ->sortable()
                        ->label('Price / Hr'),

                    Tables\Columns\TextColumn::make('capacity')
                        ->numeric()
                        ->sortable()
                        ->label('Pax'),

                    Tables\Columns\IconColumn::make('is_recording_studio')
                        ->boolean()
                        ->label('REC'),

                    Tables\Columns\IconColumn::make('is_rehearsal_room')
                        ->boolean()
                        ->label('REH'),

                    Tables\Columns\IconColumn::make('is_active')
                        ->boolean()
                        ->label('Live'),

                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_active'),
                    Tables\Filters\SelectFilter::make('store_id')
                        ->relationship('store', 'name'),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ])
                ->emptyStateHeading('No studios registered')
                ->emptyStateDescription('Start by adding a rehearsal or recording space.');
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListMusicStudios::route('/'),
                'create' => Pages\CreateMusicStudio::route('/create'),
                'edit' => Pages\EditMusicStudio::route('/{record}/edit'),
            ];
        }

        /**
         * Apply Tenant Scoping for the table.
         */
        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
                ->where('tenant_id', tenant()->id);
        }
}
