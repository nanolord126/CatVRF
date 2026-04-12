<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class EventResource extends Resource
{

    protected static ?string $model = Event::class;

        protected static ?string $navigationIcon = 'heroicon-o-ticket';

        protected static ?string $navigationGroup = 'Entertainment';

        protected static ?string $tenantOwnershipRelationshipName = 'tenant';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Event Details')
                        ->description('Configuration for the specific show or performance')
                        ->schema([
                            Forms\Components\Select::make('venue_id')
                                ->relationship('venue', 'name', fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->id))
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\DateTimePicker::make('start_at')
                                ->required()
                                ->native(false)
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('end_at', \Carbon\Carbon::parse($state)->addHours(2))),
                            Forms\Components\DateTimePicker::make('end_at')
                                ->required()
                                ->native(false),
                        ])->columns(2),

                    Forms\Components\Section::make('Commercial Configuration')
                        ->schema([
                            Forms\Components\TextInput::make('base_price_kopecks')
                                ->label('Base Price (Kopecks)')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->prefix('RUB'),
                            Forms\Components\TextInput::make('total_capacity')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->default(100),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'announced' => 'Announced',
                                    'on_sale' => 'On Sale',
                                    'sold_out' => 'Sold Out',
                                    'in_progress' => 'In Progress',
                                    'finished' => 'Finished',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->required()
                                ->default('draft'),
                            Forms\Components\TagsInput::make('tags')
                                ->placeholder('Add tags (e.g. IMAX, 3D, Premiere)'),
                        ])->columns(2),

                    Forms\Components\Section::make('Metadata')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->disabled()
                                ->dehydrated()
                                ->default(fn () => (string) Str::uuid()),
                            Forms\Components\TextInput::make('correlation_id')
                                ->disabled()
                                ->dehydrated()
                                ->default(fn () => (string) Str::uuid()),
                        ])->columns(2),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('venue.name')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('start_at')
                        ->dateTime()
                        ->sortable(),
                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'secondary' => 'draft',
                            'info' => 'announced',
                            'success' => 'on_sale',
                            'danger' => 'sold_out',
                            'primary' => 'in_progress',
                            'warning' => 'cancelled',
                        ]),
                    Tables\Columns\TextColumn::make('occupied_seats')
                        ->numeric()
                        ->label('Occupied')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('total_capacity')
                        ->numeric()
                        ->label('Cap')
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status'),
                    Tables\Filters\SelectFilter::make('venue_id')
                        ->relationship('venue', 'name', fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->id)),
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

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListEvents::route('/'),
                'create' => Pages\CreateEvent::route('/create'),
                'edit' => Pages\EditEvent::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id);
        }
}
