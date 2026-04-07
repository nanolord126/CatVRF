<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Food;

use App\Domains\Food\Models\Restaurant;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use App\Filament\Tenant\Resources\Food\RestaurantResource\Pages;

final class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;
    protected static ?string $navigationIcon = "heroicon-o-building-storefront";
    protected static ?string $navigationGroup = "Food & Catering";
    protected static ?string $tenantOwnershipRelationshipName = "tenant";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make("Restaurant Details")->schema([
                TextInput::make("name")->required()->maxLength(255),
                Textarea::make("description")->maxLength(1000),
                TextInput::make("phone")->required()->maxLength(20),
                TextInput::make("email")->email()->maxLength(255),
                TextInput::make("address")->required()->maxLength(255),
                TextInput::make("lat")->numeric()->required(),
                TextInput::make("lon")->numeric()->required(),
                TextInput::make("delivery_radius_km")->numeric()->default(5.0)->required(),
                Checkbox::make("is_active")->default(true),
            ])->columns(2),

            Section::make("Menu & Dishes")->schema([
                Repeater::make("dishes")
                    ->relationship("dishes")
                    ->schema([
                        TextInput::make("name")->required()->maxLength(255),
                        Textarea::make("description")->maxLength(500),
                        TextInput::make("price")->numeric()->required(),
                        TextInput::make("weight_grams")->numeric(),
                        TextInput::make("calories")->numeric(),
                        Checkbox::make("is_available")->default(true),
                        FileUpload::make("image_url")->image()->directory("food/dishes"),
                        
                        Repeater::make("modifiers")
                            ->schema([
                                TextInput::make("name")->required()->placeholder("e.g. Extra Cheese"),
                                TextInput::make("price_addition")->numeric()->default(0),
                            ])
                            ->defaultItems(0)
                            ->columnSpanFull()
                    ])
                    ->defaultItems(1)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")->searchable()->sortable(),
                TextColumn::make("phone")->searchable(),
                TextColumn::make("rating")->sortable(),
                BooleanColumn::make("is_active"),
            ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListRestaurants::route("/"),
            "create" => Pages\CreateRestaurant::route("/create"),
            "edit" => Pages\EditRestaurant::route("/{record}/edit"),
        ];
    }
}
