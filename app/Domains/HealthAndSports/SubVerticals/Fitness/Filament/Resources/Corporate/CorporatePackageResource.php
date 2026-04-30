<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Corporate;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Corporate\CorporatePackageModel;

final class CorporatePackageResource extends Resource
{
    protected static ?string $model = CorporatePackageModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Fitness - Corporate';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Package Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Package Name'),
                        
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->maxLength(65535)
                            ->label('Description')
                            ->rows(3),
                    ]),
                
                Forms\Components\Section::make('Pricing & Limits')
                    ->schema([
                        Forms\Components\TextInput::make('price_per_employee')
                            ->numeric()
                            ->prefix('₽')
                            ->required()
                            ->label('Price per Employee'),
                        
                        Forms\Components\TextInput::make('min_employees')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->label('Min Employees'),
                        
                        Forms\Components\TextInput::make('max_employees')
                            ->numeric()
                            ->minValue(1)
                            ->label('Max Employees')
                            ->helperText('Leave empty for unlimited'),
                        
                        Forms\Components\TextInput::make('duration_months')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix(' months')
                            ->label('Duration'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Features')
                    ->schema([
                        Forms\Components\KeyValue::make('included_services')
                            ->label('Included Services')
                            ->keyLabel('Service')
                            ->valueLabel('Description')
                            ->addable()
                            ->editable()
                            ->deletable(),
                        
                        Forms\Components\KeyValue::make('features')
                            ->label('Features')
                            ->keyLabel('Feature')
                            ->valueLabel('Description')
                            ->addable()
                            ->editable()
                            ->deletable(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Package Name'),
                
                Tables\Columns\TextColumn::make('price_per_employee')
                    ->money('RUB')
                    ->label('Price per Employee'),
                
                Tables\Columns\TextColumn::make('min_employees')
                    ->numeric()
                    ->label('Min Employees'),
                
                Tables\Columns\TextColumn::make('max_employees')
                    ->numeric()
                    ->label('Max Employees')
                    ->formatStateUsing(fn ($state) => $state ?? '∞'),
                
                Tables\Columns\TextColumn::make('duration_months')
                    ->numeric()
                    ->suffix(' months')
                    ->label('Duration'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Fitness\Filament\Resources\Corporate\Pages\ListCorporatePackages::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Corporate\Pages\CreateCorporatePackage::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Corporate\Pages\EditCorporatePackage::route('/{record}/edit'),
        ];
    }
}
