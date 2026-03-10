<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\InventoryCheckResource\Pages;
use Modules\Inventory\Models\InventoryCheck;
use Modules\Inventory\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class InventoryCheckResource extends Resource
{
    protected static ?string $model = InventoryCheck::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make('General Information')
                    ->schema([
                        Components\DatePicker::make('check_date')
                            ->default(now())
                            ->required(),
                        Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->default(auth()->id()),
                        Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'completed' => 'Completed (Adjust Stock)',
                            ])
                            ->default('draft')
                            ->required(),
                        Components\Textarea::make('notes')
                            ->rows(3),
                    ])->columns(3),

                Components\Section::make('Products to Check')
                    ->schema([
                        Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn (Components\Select $component, $state) => 
                                        $component->getContainer()->getComponent('expected_quantity')
                                            ->state(Product::find($state)?->stock ?? 0)
                                    ),
                                Components\TextInput::make('expected_quantity')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->key('expected_quantity'),
                                Components\TextInput::make('actual_quantity')
                                    ->numeric()
                                    ->required(),
                            ])->columns(3)
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('check_date')->date()->sortable(),
                Columns\TextColumn::make('user.name')->label('Officer'),
                Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'completed',
                    ]),
                Columns\TextColumn::make('notes')->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => InventoryCheckResource\Pages\ListInventoryChecks::route('/'),
            'create' => InventoryCheckResource\Pages\CreateInventoryCheck::route('/create'),
            'edit' => InventoryCheckResource\Pages\EditInventoryCheck::route('/{record}/edit'),
        ];
    }
}
