<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Restaurant\Infrastructure\Models\KitchenStationModel;
use Modules\Restaurant\Domain\Enums\KitchenStationType;

final class KitchenStationResource extends Resource
{
    protected static ?string $model = KitchenStationModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Kitchen Stations';

    protected static ?string $modelLabel = 'Kitchen Station';

    protected static ?string $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Station Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Station Name'),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'cold' => KitchenStationType::COLD->label(),
                                'hot' => KitchenStationType::HOT->label(),
                                'bar' => KitchenStationType::BAR->label(),
                                'dessert' => KitchenStationType::DESSERT->label(),
                                'expedition' => KitchenStationType::EXPEDITION->label(),
                                'grill' => KitchenStationType::GRILL->label(),
                                'pizza' => KitchenStationType::PIZZA->label(),
                                'sushi' => KitchenStationType::SUSHI->label(),
                            ])
                            ->label('Station Type'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->label('Description'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),

                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->label('Display Order'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cold' => 'blue',
                        'hot' => 'red',
                        'bar' => 'purple',
                        'dessert' => 'pink',
                        'expedition' => 'green',
                        'grill' => 'orange',
                        'pizza' => 'yellow',
                        'sushi' => 'cyan',
                    })
                    ->formatStateUsing(fn (string $state): string => KitchenStationType::from($state)->label())
                    ->label('Type'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('display_order')
                    ->sortable()
                    ->label('Order'),

                Tables\Columns\TextColumn::make('orderStatuses_count')
                    ->counts('orderStatuses')
                    ->label('Active Orders'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created'),
            ])
            ->defaultSort('display_order')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'cold' => KitchenStationType::COLD->label(),
                        'hot' => KitchenStationType::HOT->label(),
                        'bar' => KitchenStationType::BAR->label(),
                        'dessert' => KitchenStationType::DESSERT->label(),
                        'expedition' => KitchenStationType::EXPEDITION->label(),
                        'grill' => KitchenStationType::GRILL->label(),
                        'pizza' => KitchenStationType::PIZZA->label(),
                        'sushi' => KitchenStationType::SUSHI->label(),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Restaurant\Presentation\Resources\KitchenStationResource\Pages\ListKitchenStations::route('/'),
            'create' => \Modules\Restaurant\Presentation\Resources\KitchenStationResource\Pages\CreateKitchenStation::route('/create'),
            'edit' => \Modules\Restaurant\Presentation\Resources\KitchenStationResource\Pages\EditKitchenStation::route('/{record}/edit'),
        ];
    }
}
