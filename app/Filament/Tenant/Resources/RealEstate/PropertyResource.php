<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate;

use App\Domains\RealEstate\Models\Property;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;

final class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;
    protected static ?string $navigationIcon = "heroicon-o-home-modern";
    protected static ?string $navigationGroup = "Real Estate";
    protected static ?string $tenantOwnershipRelationshipName = "tenant";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make("Property Information")->schema([
                TextInput::make("title")->required()->maxLength(255),
                Textarea::make("description")->required(),
                TextInput::make("address")->required(),
                TextInput::make("lat")->numeric()->required(),
                TextInput::make("lon")->numeric()->required(),
                TextInput::make("area_sqm")->numeric()->required(),
            ])->columns(2),

            Section::make("Pricing & Classification")->schema([
                TextInput::make("price")->numeric()->required(),
                Select::make("type")->options([
                    "residential" => "Residential",
                    "commercial" => "Commercial",
                    "land" => "Land",
                ])->required(),
                Select::make("status")->options([
                    "draft" => "Draft",
                    "active" => "Active",
                    "sold" => "Sold",
                    "rented" => "Rented",
                ])->required(),
            ])->columns(3),
            
            Section::make("Media")->schema([
                Repeater::make("photos")
                    ->schema([
                        FileUpload::make("url")->image()->directory("real_estate/photos")->required(),
                        TextInput::make("caption")->maxLength(255),
                    ])
                    ->defaultItems(1)
                    ->columnSpanFull(),
            ]),

            Section::make("B2B Documents & Contracts")->schema([
                Repeater::make("documents")
                    ->schema([
                        TextInput::make("title")->required(),
                        FileUpload::make("file")->acceptedFileTypes(["application/pdf"])->directory("real_estate/docs")->required(),
                    ])
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("title")->searchable()->sortable(),
                TextColumn::make("type")->sortable(),
                TextColumn::make("price")->money("RUB")->sortable(),
                TextColumn::make("status")->badge()->colors([
                    "primary" => "draft",
                    "success" => "active",
                    "danger" => "sold",
                    "warning" => "rented",
                ]),
                BooleanColumn::make("is_active"),
            ])
            ->filters([
                SelectFilter::make("status")->options([
                    "active" => "Active",
                    "draft" => "Draft",
                    "sold" => "Sold",
                    "rented" => "Rented",
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListProperties::route("/"),
            "create" => Pages\CreateProperty::route("/create"),
            "edit" => Pages\EditProperty::route("/{record}/edit"),
        ];
    }
}
