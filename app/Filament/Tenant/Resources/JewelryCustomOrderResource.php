<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Luxury\Jewelry\Models\JewelryCustomOrder;
use App\Domains\Luxury\Jewelry\Models\JewelryProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * JewelryCustomOrderResource (Layer 5/9)
 * Handles bespoke jewelry manufacturing orders with AI specs and manufacturing workflows.
 */
class JewelryCustomOrderResource extends Resource
{
    protected static ?string $model = JewelryCustomOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Jewelry Management';
    protected static ?string $navigationLabel = 'Bespoke Orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer & Reference Information')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('product_reference_id')
                            ->relationship('productReference', 'name')
                            ->hint('Base product inspiration.')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('order_uuid')
                            ->disabled()
                            ->required()
                            ->maxLength(36)
                            ->placeholder('Auto-generated UUID.'),
                        Forms\Components\TextInput::make('metal_type')
                            ->required()
                            ->placeholder('e.g. 18k White Gold, PT950'),
                        Forms\Components\TextInput::make('item_size')
                            ->placeholder('e.g. Ring size 17, Necklace 45cm'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending Approval',
                                'approved' => 'Approved (Awaiting Payment)',
                                'processing' => 'In Manufacture',
                                'qa' => 'Quality Assurance (Gemologist Check)',
                                'ready' => 'Ready for Shipping',
                                'completed' => 'Delivered to Client',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                    ]),

                Forms\Components\Section::make('Design & AI Specifications')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Textarea::make('engraving_text')
                            ->columnSpan(2)
                            ->placeholder('Maximum 30 characters recommended.'),
                        Forms\Components\KeyValue::make('ai_specifications_json')
                            ->label('Manufacturing AI Blueprints')
                            ->keyLabel('Blueprint Path/Param')
                            ->valueLabel('Specification Value')
                            ->columnSpan(2)
                            ->hint('AI generated paths (Vector Path, Laser Depth, Stone Layout).'),
                        Forms\Components\RichEditor::make('customer_notes')
                            ->columnSpan(2)
                            ->maxLength(1000),
                    ]),

                Forms\Components\Section::make('Financials & Logistics')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->numeric()
                            ->required()
                            ->suffix('Kopecks (approx.)')
                            ->hint('Final agreed price for the custom piece.'),
                        Forms\Components\TextInput::make('manufacturing_days_est')
                            ->numeric()
                            ->required()
                            ->default(14)
                            ->hint('Estimated days to complete manufacturing.'),
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Payment Captured')
                            ->disabled()
                            ->onIcon('heroicon-m-check-circle')
                            ->offIcon('heroicon-m-x-circle'),
                    ]),

                Forms\Components\Section::make('Internal Audit & Correlation')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('correlation_id')
                            ->label('Security Tracking (Correlation ID)')
                            ->required()
                            ->disabled()
                            ->hint('Auto-assigned for audit logging.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_uuid')
                    ->label('Request ID')
                    ->searchable()
                    ->copyable()
                    ->badge(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'pending',
                        'warning' => 'processing',
                        'success' => 'ready',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('RUB', locale: 'ru')
                    ->state(fn (JewelryCustomOrder $record) => $record->total_price / 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('manufacturing_days_est')
                    ->label('Est Days')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_paid')
                    ->boolean()
                    ->label('Paid'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Approval',
                        'approved' => 'Approved',
                        'processing' => 'In Manufacture',
                        'ready' => 'Ready',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\TernaryFilter::make('is_paid'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_as_qa')
                    ->label('Quality Assay')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('warning')
                    ->action(fn (JewelryCustomOrder $record) => $record->update(['status' => 'qa']))
                    ->visible(fn ($record) => $record->status === 'processing'),
                Tables\Actions\Action::make('mark_as_ready')
                    ->label('Set Ready')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (JewelryCustomOrder $record) => $record->update(['status' => 'ready']))
                    ->visible(fn ($record) => $record->status === 'qa'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'productReference'])
            ->latest();
    }
}
