<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto;

use App\Domains\Auto\Models\AutoPart;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

final class AutoPartResource extends Resource
{
    protected static ?string $model = AutoPart::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationGroup = 'Auto';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),

                Forms\Components\TextInput::make('sku')
                    ->required(),

                Forms\Components\TextInput::make('current_stock')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('min_stock_threshold')
                    ->numeric()
                    ->default(10),

                Forms\Components\Textarea::make('description')
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

                Tables\Columns\TextColumn::make('sku')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('current_stock')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hold_stock')
                    ->label('Hold'),

                Tables\Columns\TextColumn::make('price')
                    ->money('RUB'),

                Tables\Columns\BadgeColumn::make('current_stock')
                    ->color(fn (int $state): string => $state < 10 ? 'danger' : 'success')
                    ->label('Stock Status'),
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
            'index' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\ListAutoParts::route('/'),
            'create' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\CreateAutoPart::route('/create'),
            'view' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\ViewAutoPart::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\EditAutoPart::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}
