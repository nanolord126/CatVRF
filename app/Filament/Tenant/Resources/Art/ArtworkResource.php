<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Art;


use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class ArtworkResource extends Resource
{

    protected static ?string $model = Artwork::class;

        protected static ?string $navigationGroup = 'Art Vertical';

        protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General Information')
                        ->description('Details of the piece of art.')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (string $state, Forms\Set $set) => $set('slug', Str::slug($state))),

                            Forms\Components\TextInput::make('slug')
                                ->required()
                                ->disabled()
                                ->dehydrated()
                                ->unique(Artwork::class, 'slug', ignoreRecord: true),

                            Forms\Components\Select::make('gallery_id')
                                ->label('Gallery')
                                ->relationship('gallery', 'name')
                                ->required()
                                ->preload()
                                ->searchable(),

                            Forms\Components\Select::make('artist_id')
                                ->label('Artist')
                                ->relationship('artist', 'name')
                                ->required()
                                ->preload()
                                ->searchable(),

                            Forms\Components\TextInput::make('price_cents')
                                ->label('Price (Cents)')
                                ->numeric()
                                ->required()
                                ->prefix('RUB')
                                ->helperText('Store price in cents to prevent precision loss.'),
                        ])->columns(2),

                    Forms\Components\Section::make('Technical Details')
                        ->schema([
                            Forms\Components\RichEditor::make('description')
                                ->required()
                                ->columnSpanFull(),

                            Forms\Components\Select::make('status')
                                ->options([
                                    'available' => 'Available',
                                    'sold' => 'Sold',
                                    'pending' => 'Pending Review',
                                    'withdrawn' => 'Withdrawn',
                                ])
                                ->required()
                                ->default('pending'),

                            Forms\Components\KeyValue::make('dimensions')
                                ->label('Physical Dimensions')
                                ->helperText('E.g. width: 100, height: 150, depth: 5'),

                            Forms\Components\TagsInput::make('tags')
                                ->label('Categorization Tags')
                                ->placeholder('Abstract, Oil, 2026, Renaissance'),
                        ])->columns(2),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('title')
                        ->searchable()
                        ->sortable()
                        ->weight(FontWeight::Bold),

                    Tables\Columns\TextColumn::make('gallery.name')
                        ->label('Gallery')
                        ->sortable()
                        ->toggleable(),

                    Tables\Columns\TextColumn::make('artist.name')
                        ->label('Artist')
                        ->sortable()
                        ->toggleable(),

                    Tables\Columns\TextColumn::make('price_cents')
                        ->label('Price')
                        ->money('RUB', divideBy: 100)
                        ->sortable(),

                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'success' => 'available',
                            'danger' => 'sold',
                            'warning' => 'pending',
                            'secondary' => 'withdrawn',
                        ]),

                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'available' => 'Available',
                            'sold' => 'Sold',
                            'pending' => 'Pending Review',
                        ]),
                    Tables\Filters\SelectFilter::make('gallery_id')
                        ->label('By Gallery')
                        ->relationship('gallery', 'name'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('purchase')
                        ->icon('heroicon-o-shopping-cart')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Artwork $record) => $record->status === 'available')
                        ->action(function (Artwork $record, ArtService $service) {
                            try {
                                $service->purchaseArtwork($this->guard->id() ?? 1, $record->id);
                                Notification::make()
                                    ->title('Purchase successful')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Purchase failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])
                ->bulkActions([
                    Tables\Actions\DeleteBulkAction::make(),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes()
                ->where('tenant_id', (tenant()->id ?? 1))
                ->with(['gallery', 'artist']);
        }

        public static function getPages(): array
        {
            return [
                'index' => ArtworkResource\Pages\ListArtworks::route('/'),
                'create' => ArtworkResource\Pages\CreateArtwork::route('/create'),
                'edit' => ArtworkResource\Pages\EditArtwork::route('/{record}/edit'),
            ];
        }
}
