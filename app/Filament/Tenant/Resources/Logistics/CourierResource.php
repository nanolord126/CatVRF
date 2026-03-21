<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics;

use App\Domains\Logistics\Models\Courier;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

final class CourierResource extends Resource
{
    protected static ?string $model = Courier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Logistics';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('full_name')
                    ->required(),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required(),

                Forms\Components\Select::make('zone_id')
                    ->options([
                        'north' => 'North Zone',
                        'south' => 'South Zone',
                        'east' => 'East Zone',
                        'west' => 'West Zone',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('current_load')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_available')
                    ->default(true),

                Forms\Components\TextInput::make('rating')
                    ->numeric()
                    ->default(0)
                    ->max(5),
            ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->sortable(),

                Tables\Columns\TextColumn::make('zone_id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_load')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_available')
                    ->boolean(),

                Tables\Columns\TextColumn::make('rating')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('zone_id')
                    ->options([
                        'north' => 'North Zone',
                        'south' => 'South Zone',
                        'east' => 'East Zone',
                        'west' => 'West Zone',
                    ]),

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
            'index' => \App\Filament\Tenant\Resources\Logistics\CourierResource\Pages\ListCouriers::route('/'),
            'create' => \App\Filament\Tenant\Resources\Logistics\CourierResource\Pages\CreateCourier::route('/create'),
            'view' => \App\Filament\Tenant\Resources\Logistics\CourierResource\Pages\ViewCourier::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\Logistics\CourierResource\Pages\EditCourier::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}
