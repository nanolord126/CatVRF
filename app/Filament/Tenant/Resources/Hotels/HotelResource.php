<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels;

use App\Domains\Hotels\Models\Hotel;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

final class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Hotels';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),

                Forms\Components\TextInput::make('address')
                    ->required(),

                Forms\Components\TextInput::make('stars')
                    ->numeric()
                    ->min(1)
                    ->max(5),

                Forms\Components\TextInput::make('total_rooms')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('rating')
                    ->numeric()
                    ->default(0)
                    ->max(5),

                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('amenities_json')
                    ->label('Amenities (JSON)')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('address')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stars')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_rooms')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
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
            'index' => \App\Filament\Tenant\Resources\Hotels\HotelResource\Pages\ListHotels::route('/'),
            'create' => \App\Filament\Tenant\Resources\Hotels\HotelResource\Pages\CreateHotel::route('/create'),
            'view' => \App\Filament\Tenant\Resources\Hotels\HotelResource\Pages\ViewHotel::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\Hotels\HotelResource\Pages\EditHotel::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}
