<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Art;

use App\Models\Art\ArtGallery;
use App\Models\Art\Artist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

/**
 * ArtistResource — Management for creators associated with galleries.
 */
final class ArtistResource extends Resource
{
    protected static ?string $model = Artist::class;

    protected static ?string $navigationGroup = 'Art Vertical';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Artist Profile')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('gallery_id')
                            ->relationship('gallery', 'name')
                            ->required()
                            ->preload()
                            ->searchable(),

                        Forms\Components\RichEditor::make('bio')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('experience_years')
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.1)
                            ->default(0.0),
                    ])->columns(2),

                Forms\Components\Section::make('Specialization & Meta')
                    ->schema([
                        Forms\Components\TagsInput::make('specialization')
                            ->label('Creative Fields')
                            ->placeholder('Modernism, Cubism, Street Art'),

                        Forms\Components\Toggle::make('is_verified')
                            ->label('Platform Verified')
                            ->default(false),
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

                Tables\Columns\TextColumn::make('gallery.name')
                    ->label('Associated Gallery')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Platform Rating')
                    ->numeric(1)
                    ->color('warning'),

                ToggleColumn::make('is_verified')
                    ->label('Verified Artist')
                    ->disabled(!auth()->user()?->hasRole('admin')),

                Tables\Columns\TextColumn::make('artworks_count')
                    ->label('Total Pieces')
                    ->counts('artworks')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified'),
                Tables\Filters\SelectFilter::make('gallery_id')
                    ->relationship('gallery', 'name'),
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
            'index' => ArtistResource\Pages\ListArtists::route('/'),
            'create' => ArtistResource\Pages\CreateArtist::route('/create'),
            'edit' => ArtistResource\Pages\EditArtist::route('/{record}/edit'),
        ];
    }
}
