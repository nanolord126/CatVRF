<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\FashionNFTAvatarResource\Pages;

final class FashionNFTAvatarResource extends Resource
{
    protected static ?string $model = \App\Models\FashionNFTAvatar::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Fashion AI';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('NFT Avatar Information')
                    ->schema([
                        Forms\Components\TextInput::make('avatar_url')
                            ->url()
                            ->required(),
                        Forms\Components\KeyValue::make('metadata')
                            ->keyLabel('Property')
                            ->valueLabel('Value')
                            ->required(),
                        Forms\Components\TextInput::make('points_threshold')
                            ->numeric()
                            ->required()
                            ->default(5000),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular(),
                Tables\Columns\TextColumn::make('metadata')
                    ->formatStateUsing(fn ($state): string => $state['style'] ?? 'N/A'),
                Tables\Columns\TextColumn::make('points_threshold')
                    ->label('Threshold')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFashionNFTAvatars::route('/'),
            'create' => Pages\CreateFashionNFTAvatar::route('/create'),
            'view' => Pages\ViewFashionNFTAvatar::route('/{record}'),
            'edit' => Pages\EditFashionNFTAvatar::route('/{record}/edit'),
        ];
    }
}
