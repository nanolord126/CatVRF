<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Furniture\Models\FurnitureCustomOrder;
use App\Domains\Furniture\Models\FurnitureStore;
use App\Domains\Furniture\Models\FurnitureProduct;
use App\Domains\Furniture\Models\FurnitureRoomType;
use App\Domains\Furniture\Services\FurnitureDomainService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * FurnitureCustomOrderResource (Layer 6/9)
 * Order management for custom interior projects and individual furniture requests.
 */
class FurnitureCustomOrderResource extends Resource
{
    protected static ?string $model = FurnitureCustomOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Furniture Marketplace';
    protected static ?string $navigationLabel = 'Project Orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Order Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('store_id')
                            ->relationship('store', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('room_type_id')
                            ->relationship('roomType', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_phone')
                            ->tel()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending Confirmation',
                                'confirmed' => 'Project Confirmed',
                                'processing' => 'In Manufacturing',
                                'completed' => 'Delivered & Assembled',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\TextInput::make('total_price_kopecks')
                            ->numeric()
                            ->required()
                            ->label('Project Estimate (Kopecks)'),
                    ]),

                Forms\Components\Section::make('AI Implementation Specification')
                    ->columns(2)
                    ->schema([
                        Forms\Components\JsonEditor::make('ai_specification')
                            ->label('Project Technical Blueprint')
                            ->required()
                            ->helperText('Contains technical specs, materials, and colors from AI/CAD analysis.'),
                        Forms\Components\TagsInput::make('tags')
                            ->placeholder('Modern, Dark Wood, Minimalist'),
                        Forms\Components\TextInput::make('delivery_address')
                            ->required()
                            ->maxLength(500),
                        Forms\Components\FileUpload::make('sketch_photo_path')
                            ->image()
                            ->directory('furniture/sketches')
                            ->label('Reference Photo / Sketch'),
                    ]),

                Forms\Components\Section::make('Correlation & Audit Trace')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('correlation_id')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('System Trace UUID'),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->disabled()
                            ->label('Date of Order Arrival'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'confirmed',
                        'info' => 'processing',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price_kopecks')
                    ->money('RUB', locale: 'ru')
                    ->state(fn (FurnitureCustomOrder $record) => $record->total_price_kopecks / 100)
                    ->sortable()
                    ->label('Total (RUB)'),
                Tables\Columns\TextColumn::make('roomType.name')
                    ->sortable()
                    ->label('Room Concept'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Order Date'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('room_type_id')
                    ->relationship('roomType', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('Confirm Project')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(fn (FurnitureCustomOrder $record) => $record->update(['status' => 'confirmed']))
                    ->visible(fn (FurnitureCustomOrder $record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['store', 'roomType'])
            ->latest();
    }
}
