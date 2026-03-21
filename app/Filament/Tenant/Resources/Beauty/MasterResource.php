<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\Master;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

final class MasterResource extends Resource
{
    protected static ?string $model = Master::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Beauty';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('full_name')
                    ->required(),

                Forms\Components\TextInput::make('specialization')
                    ->required(),

                Forms\Components\TextInput::make('experience_years')
                    ->numeric()
                    ->default(0),

                Forms\Components\TextInput::make('rating')
                    ->numeric()
                    ->default(0)
                    ->max(5),

                Forms\Components\Textarea::make('bio')
                    ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('specialization')
                    ->sortable(),

                Tables\Columns\TextColumn::make('experience_years')
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
            'index' => \App\Filament\Tenant\Resources\Beauty\MasterResource\Pages\ListMasters::route('/'),
            'create' => \App\Filament\Tenant\Resources\Beauty\MasterResource\Pages\CreateMaster::route('/create'),
            'view' => \App\Filament\Tenant\Resources\Beauty\MasterResource\Pages\ViewMaster::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\Beauty\MasterResource\Pages\EditMaster::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}
