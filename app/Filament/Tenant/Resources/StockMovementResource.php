<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\StockMovementResource\Pages;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Filament\Forms\Get;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrows-up-down';
    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make('Stock Movement Details')
                    ->description('Record stock movement of products.')
                    ->schema([
                        Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->required(),
                        Components\Select::make('type')
                            ->options([
                                'in' => 'Stock In (Arrival)',
                                'out' => 'Stock Out (Consumption)',
                                'adjustment' => 'Adjustment (Correction)',
                            ])
                            ->required()
                            ->reactive(),
                        Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Components\Select::make('reference_type')
                            ->options([
                                'Modules\Hotels\Models\Booking' => 'Hotel Booking',
                                'Modules\BeautyMasters\Models\Appointment' => 'Beauty Appointment',
                            ])
                            ->required(fn (get $get) => $get('type') === 'out')
                            ->visible(fn (get $get) => $get('type') === 'out'),
                        Components\TextInput::make('reference_id')
                            ->numeric()
                            ->required(fn (get $get) => $get('type') === 'out')
                            ->visible(fn (get $get) => $get('type') === 'out')
                            ->label('Reference ID (Booking/Appointment)'),
                        Components\TextInput::make('reason')
                            ->placeholder('e.g. New arrival, Sale, Damaged item'),
                        Components\Toggle::make('is_approved')
                            ->label('Is Approved')
                            ->visible(fn () => auth()->user()?->hasRole(['Owner', 'Manager']))
                            ->default(fn () => auth()->user()?->hasRole(['Owner', 'Manager'])),
                        Components\TextInput::make('correlation_id')
                            ->default(fn () => (string) Str::uuid())
                            ->readOnly()
                            ->label('Correlation ID (Audit Trace)'),
                        Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Columns\TextColumn::make('product.name')->searchable(),
                Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'in',
                        'danger' => 'out',
                        'warning' => 'adjustment',
                    ]),
                Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Apprv')
                    ->sortable(),
                Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'requires_approval',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Columns\TextColumn::make('quantity')->sortable(),
                Columns\TextColumn::make('reference_id')
                    ->label('Ref')
                    ->description(fn (StockMovement $record): string => (string)$record->reference_type)
                    ->toggleable(),
                Columns\TextColumn::make('reason')->limit(30),
                Columns\TextColumn::make('correlation_id')->label('Audit Trace')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'requires_approval' => 'Requires Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approved Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->action(fn (StockMovement $record) => $record->update([
                        'is_approved' => true,
                        'status' => 'approved'
                    ]))
                    ->visible(fn (StockMovement $record) => !$record->is_approved && auth()->user()?->hasRole(['Owner', 'Manager']))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('approveSelected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update([
                            'is_approved' => true,
                            'status' => 'approved',
                        ]))
                        ->visible(fn () => auth()->user()?->hasRole(['Owner', 'Manager'])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => StockMovementResource\Pages\ListStockMovements::route('/'),
            'create' => StockMovementResource\Pages\CreateStockMovement::route('/create'),
        ];
    }
}
