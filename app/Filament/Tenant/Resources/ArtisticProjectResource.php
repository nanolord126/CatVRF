<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Tenant\Resources\ArtisticProjectResource\Pages;

final class ArtisticProjectResource extends Resource
{
    protected static ?string $model = 'App\\Domains\\Archived\\Art\\ArtisticServices\\Models\\ArtisticProject';

    protected static ?string $navigationLabel = 'Art Projects';
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArtisticProjects::route('/'),
            'create' => Pages\CreateArtisticProject::route('/create'),
            'edit' => Pages\EditArtisticProject::route('/{record}/edit'),
            'view' => Pages\ViewArtisticProject::route('/{record}'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Проект')
                    ->schema([
                        Forms\Components\TextInput::make('artist_id')->required()->numeric(),
                        Forms\Components\TextInput::make('client_id')->required()->numeric(),
                        Forms\Components\TextInput::make('project_type')->required(),
                        Forms\Components\TextInput::make('artistic_hours')->required()->numeric(),
                        Forms\Components\DateTimePicker::make('due_date')->required(),
                        Forms\Components\TextInput::make('status')->disabled(),
                        Forms\Components\TextInput::make('payment_status')->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('artist_id'),
                Tables\Columns\TextColumn::make('client_id'),
                Tables\Columns\TextColumn::make('project_type'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('payment_status'),
                Tables\Columns\TextColumn::make('due_date')->dateTime(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
