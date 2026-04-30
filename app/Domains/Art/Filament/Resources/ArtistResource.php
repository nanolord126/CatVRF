<?php

declare(strict_types=1);

namespace App\Domains\Art\Filament\Resources;

use App\Domains\Art\Models\Artist;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\Art\Filament\Resources\ArtistResource\Pages\CreateArtist;
use App\Domains\Art\Filament\Resources\ArtistResource\Pages\EditArtist;
use App\Domains\Art\Filament\Resources\ArtistResource\Pages\ListArtists;

/**
 * Filament Resource: Artist.
 *
 * CANON 2026 — Layer 9: Filament admin panel resource.
 * Tenant-scoped через global scope.
 */
final class ArtistResource extends BaseOptimizedResource
{
    protected static ?string $model = Artist::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Art';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label('Описание')
                ->maxLength(5000),
            Forms\Components\Select::make('status')
                ->label('Статус')
                ->options([
                    'active'   => 'Активный',
                    'draft'    => 'Черновик',
                    'archived' => 'Архив',
                ])
                ->default('active'),
            Forms\Components\TagsInput::make('tags')
                ->label('Теги'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'   => 'Активный',
                        'draft'    => 'Черновик',
                        'archived' => 'Архив',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListArtists::route('/'),
            'create' => CreateArtist::route('/create'),
            'edit'   => EditArtist::route('/{record}/edit'),
        ];
    }

    /**
     * Relations to eager load for Art
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
