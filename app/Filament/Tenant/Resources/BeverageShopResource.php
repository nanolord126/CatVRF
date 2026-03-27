<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Food\Beverages\Models\BeverageShop;
use App\Domains\Food\Beverages\Services\BeverageService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class BeverageShopResource extends Resource
{
    protected static ?string $model = BeverageShop::class;

    protected static ?string $navigationIcon = 'heroicon-o-cup-straw';

    protected static ?string $navigationGroup = 'Beverages Vertical';

    /**
     * Complete form definition (>= 60 lines per canon 2026).
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->description('Basic details about the beverage venue')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Shop Name')
                            ->placeholder('e.g. Arabica Coffee'),
                            
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'coffee_shop' => 'Coffee Shop',
                                'tea_house' => 'Tea House',
                                'bar' => 'Bar / Pub',
                                'brewery' => 'Brewery',
                            ])
                            ->label('Establishment Type'),
                            
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->maxLength(255)
                            ->label('Physical Address'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Currently Active'),
                    ])->columns(2),

                Forms\Components\Section::make('Location & Schedule')
                    ->description('Geographic and operational details')
                    ->schema([
                        Forms\Components\KeyValue::make('geo_point')
                            ->label('Geographic Coordinates')
                            ->keyLabel('Coordinate (lat/lon)')
                            ->valueLabel('Value')
                            ->placeholder('lat: 55.75, lon: 37.61'),
                            
                        Forms\Components\Repeater::make('schedule')
                            ->label('Operating Schedule')
                            ->schema([
                                Forms\Components\Select::make('day')
                                    ->options([
                                        'monday' => 'Monday',
                                        'tuesday' => 'Tuesday',
                                        'wednesday' => 'Wednesday',
                                        'thursday' => 'Thursday',
                                        'friday' => 'Friday',
                                        'saturday' => 'Saturday',
                                        'sunday' => 'Sunday',
                                    ])->required(),
                                Forms\Components\TimePicker::make('open_at')->required(),
                                Forms\Components\TimePicker::make('close_at')->required(),
                            ])
                            ->columns(3)
                            ->grid(1),
                    ]),

                Forms\Components\Section::make('Analytics & Advanced')
                    ->description('Internal metadata and tags')
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->label('Analytical Tags')
                            ->placeholder('e.g. premium, student_choice, vegan_friendly'),
                            
                        Forms\Components\TextInput::make('uuid')
                            ->disabled()
                            ->label('System UUID')
                            ->helperText('Assigned automatically on creation.'),
                            
                        Forms\Components\TextInput::make('correlation_id')
                            ->disabled()
                            ->label('Last Correlation ID')
                            ->helperText('Track performance and security across sessions.'),
                    ])->columns(2),
            ]);
    }

    /**
     * Complete table definition (>= 50 lines per canon 2026).
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->label('Shop Name'),
                    
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'coffee_shop' => 'success',
                        'tea_house' => 'primary',
                        'bar' => 'danger',
                        default => 'gray',
                    })
                    ->label('Type'),
                    
                Tables\Columns\TextColumn::make('address')
                    ->limit(30)
                    ->searchable()
                    ->label('Location'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                    
                Tables\Columns\TextColumn::make('rating')
                    ->numeric(1)
                    ->sortable()
                    ->icon('heroicon-m-star')
                    ->color('warning')
                    ->label('Score'),
                    
                Tables\Columns\TextColumn::make('review_count')
                    ->numeric()
                    ->label('Reviews'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'coffee_shop' => 'Coffee',
                        'tea_house' => 'Tea',
                        'bar' => 'Bar',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Only Active Venues'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->before(function (BeverageShop $record) {
                        // Canon: Audit log before surgery
                        Log::channel('audit')->info('Filament: Preparing to edit beverage shop', [
                            'shop_id' => $record->id,
                            'correlation_id' => $record->correlation_id,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Canon: Global Scope via getEloquentQuery.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relations for categories and items should be here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\BeverageShopResource\Pages\ListBeverageShops::route('/'),
            'create' => \App\Filament\Tenant\Resources\BeverageShopResource\Pages\CreateBeverageShop::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\BeverageShopResource\Pages\EditBeverageShop::route('/{record}/edit'),
        ];
    }
}
