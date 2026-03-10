<?php

namespace App\Filament\B2B\Resources;

use App\Models\B2B\B2BRecommendation;
use App\Models\B2B\B2BProduct;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Infolist;

/**
 * Filament Resource for B2B AI Recommendations in the B2B Panel.
 * Managing AI-driven business matching across the 2026 Ecosystem.
 */
class B2BRecommendationResource extends Resource
{
    protected static ?string $model = B2BRecommendation::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    
    protected static ?string $navigationGroup = 'AI Intelligence';

    protected static ?string $modelLabel = 'AI Recommendation';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Matching Context')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Buyer (Tenant)')
                            ->options(Tenant::all()->pluck('name', 'id'))
                            ->searchable(),
                        
                        Forms\Components\Select::make('supplier_id')
                            ->label('Seller (Supplier)')
                            ->relationship('supplier', 'name'), // Assuming supplier relation in B2BRecommendation
                        
                        Forms\Components\TextInput::make('type')
                            ->required(),
                        
                        Forms\Components\TextInput::make('match_score')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(1),
                    ])->columns(2),

                Forms\Components\Section::make('AI Reasoning & Explanability')
                    ->schema([
                        Forms\Components\KeyValue::make('reasoning')
                            ->keyLabel('Metric')
                            ->valueLabel('Value'),
                        
                        Forms\Components\TextInput::make('embeddings_version')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'SupplierBuy' => 'success',
                        'TenantSell' => 'info',
                        'Alternative' => 'warning',
                        'CrossSell' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Buyer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('recommendable_type')
                    ->label('Target Entity')
                    ->formatStateUsing(fn (string $state) => class_basename($state)),
                
                Tables\Columns\TextColumn::make('recommendable_id')
                    ->label('Target ID'),

                Tables\Columns\ProgressBarColumn::make('match_score')
                    ->label('AI Match Score')
                    ->progress(fn ($state) => $state * 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'SupplierBuy' => 'Opportunities for Buyers',
                        'TenantSell' => 'Opportunities for Sellers',
                    ]),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('AI Decision Analysis')
                    ->schema([
                        TextEntry::make('match_score')
                            ->numeric(2)
                            ->color('success')
                            ->weight('bold'),
                        
                        TextEntry::make('reasoning.text')
                            ->label('Natural Language Reasoning'),
                        
                        TextEntry::make('type')->badge(),
                        
                        TextEntry::make('correlation_id')->fontFamily('mono')->copyable(),
                    ])->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => B2BRecommendationResource\ListB2BRecommendations::route('/'),
            'create' => B2BRecommendationResource\CreateB2BRecommendation::route('/create'),
            'view' => B2BRecommendationResource\ViewB2BRecommendation::route('/{record}'),
            'edit' => B2BRecommendationResource\EditB2BRecommendation::route('/{record}/edit'),
        ];
    }
}
