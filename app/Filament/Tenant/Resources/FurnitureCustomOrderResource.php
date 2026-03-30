<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FurnitureCustomOrderResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFurnitureCustomOrder::route('/'),
                'create' => Pages\\CreateFurnitureCustomOrder::route('/create'),
                'edit' => Pages\\EditFurnitureCustomOrder::route('/{record}/edit'),
                'view' => Pages\\ViewFurnitureCustomOrder::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFurnitureCustomOrder::route('/'),
                'create' => Pages\\CreateFurnitureCustomOrder::route('/create'),
                'edit' => Pages\\EditFurnitureCustomOrder::route('/{record}/edit'),
                'view' => Pages\\ViewFurnitureCustomOrder::route('/{record}'),
            ];
        }
}
