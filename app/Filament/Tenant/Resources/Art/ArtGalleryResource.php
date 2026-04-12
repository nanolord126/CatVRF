<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Art;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class ArtGalleryResource extends Resource
{

    protected static ?string $model = ArtGallery::class;

        protected static ?string $navigationGroup = 'Art Vertical';

        protected static ?string $navigationIcon = 'heroicon-o-home-modern';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Gallery Profile')
                        ->description('Public information for Art & Gallery discovery.')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('address')
                                ->required()
                                ->maxLength(500),

                            Forms\Components\RichEditor::make('description')
                                ->required()
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('rating')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(5.0)
                                ->step(0.1)
                                ->disabled()
                                ->default(0.0),

                            Forms\Components\Toggle::make('is_verified')
                                ->label('Verified Venue')
                                ->default(false),
                        ])->columns(2),

                    Forms\Components\Section::make('Availability Settings')
                        ->schema([
                            Forms\Components\KeyValue::make('schedule_json')
                                ->label('Weekly Schedule')
                                ->helperText('Work hours: key = day, value = hours'),
                        ])->columns(1),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('address')
                        ->searchable()
                        ->limit(50),

                    Tables\Columns\TextColumn::make('rating')
                        ->numeric(1)
                        ->color('warning'),

                    Tables\Columns\ToggleColumn::make('is_verified')
                        ->label('Verified')
                        ->disabled()
                        ->onIcon('heroicon-m-check-badge')
                        ->offIcon('heroicon-o-shield-exclamation'),

                    Tables\Columns\TextColumn::make('artworks_count')
                        ->label('Artwork count')
                        ->counts('artworks')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_verified')
                        ->label('Verification Status'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes()
                ->where('tenant_id', (tenant()->id ?? 1))
                ->withCount('artworks');
        }

        public static function getPages(): array
        {
            return [
                'index' => ArtGalleryResource\Pages\ListArtGalleries::route('/'),
                'create' => ArtGalleryResource\Pages\CreateArtGallery::route('/create'),
                'edit' => ArtGalleryResource\Pages\EditArtGallery::route('/{record}/edit'),
            ];
        }
}
