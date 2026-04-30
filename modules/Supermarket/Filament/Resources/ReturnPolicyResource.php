<?php

declare(strict_types=1);

namespace Modules\Supermarket\Filament\Resources;

use Modules\Supermarket\Infrastructure\Models\ReturnPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;

class ReturnPolicyResource extends Resource
{
    protected static ?string $model = ReturnPolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationGroup = 'Supermarket';
    
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Policy Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('sub_vertical')
                            ->label('Sub Vertical')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('e.g., meat_shops, vegan_products, confectionery'),
                        
                        Forms\Components\TextInput::make('vertical')
                            ->label('Vertical')
                            ->default('supermarket')
                            ->required(),
                        
                        Forms\Components\TextInput::make('max_days')
                            ->label('Max Days for Return')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('days')
                            ->default(3),
                        
                        Forms\Components\Toggle::make('cold_chain_only_defect')
                            ->label('Cold Chain - Defect Only')
                            ->helperText('Cold chain items can only be returned if spoiled/damaged'),
                        
                        Forms\Components\Toggle::make('requires_photo')
                            ->label('Requires Photo Evidence')
                            ->default(false),
                        
                        Forms\Components\Toggle::make('requires_temperature')
                            ->label('Requires Temperature Check')
                            ->default(false),
                        
                        Forms\Components\TextInput::make('max_refund_percent')
                            ->label('Max Refund Percent')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(100),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Allowed Reasons')
                    ->schema([
                        Forms\Components\CheckboxList::make('allowed_reasons')
                            ->label('Permitted Return Reasons')
                            ->options([
                                'spoiled' => 'Spoiled',
                                'wrong_item' => 'Wrong Item',
                                'changed_mind' => 'Changed Mind',
                                'damaged' => 'Damaged',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sub_vertical')
                    ->label('Sub Vertical')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('vertical')
                    ->label('Vertical')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('max_days')
                    ->label('Max Days')
                    ->sortable(),
                
                IconColumn::make('cold_chain_only_defect')
                    ->label('Cold Chain Defect Only')
                    ->boolean(),
                
                IconColumn::make('requires_photo')
                    ->label('Photo Required')
                    ->boolean(),
                
                IconColumn::make('requires_temperature')
                    ->label('Temperature Required')
                    ->boolean(),
                
                TextColumn::make('max_refund_percent')
                    ->label('Max Refund %')
                    ->sortable(),
                
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                
                Tables\Filters\SelectFilter::make('vertical')
                    ->options([
                        'supermarket' => 'Supermarket',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => \Modules\Supermarket\Filament\Resources\ReturnPolicyResource\Pages\ListReturnPolicies::route('/'),
            'create' => \Modules\Supermarket\Filament\Resources\ReturnPolicyResource\Pages\CreateReturnPolicy::route('/create'),
            'view' => \Modules\Supermarket\Filament\Resources\ReturnPolicyResource\Pages\ViewReturnPolicy::route('/{record}'),
            'edit' => \Modules\Supermarket\Filament\Resources\ReturnPolicyResource\Pages\EditReturnPolicy::route('/{record}/edit'),
        ];
    }
}
