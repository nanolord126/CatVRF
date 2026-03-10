<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\FlowersItemResource\Pages;
use App\Models\Tenants\FlowersItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FlowersItemResource extends Resource
{
    protected static ?string $model = FlowersItem::class;
    protected static ?string $navigationGroup = 'Marketplace';
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Core Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('price')->numeric()->prefix('$')->required(),
                        Forms\Components\Textarea::make('description')->columnSpanFull(),
                        Forms\Components\KeyValue::make('composition')->label('Bouquet Composition'),
                        Forms\Components\Toggle::make('is_available')->default(true),
                        Forms\Components\Hidden::make('correlation_id')->default(fn () => (string) Str::uuid()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('price')->money('USD')->sortable(),
                Tables\Columns\IconColumn::make('is_available')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_available'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlowersItems::route('/'),
            'create' => Pages\CreateFlowersItem::route('/create'),
            'edit' => Pages\EditFlowersItem::route('/{record}/edit'),
        ];
    }
}
