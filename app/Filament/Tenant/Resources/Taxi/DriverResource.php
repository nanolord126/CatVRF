<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Taxi;

use App\Domains\Taxi\Models\Driver;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Tenant\Resources\Taxi\DriverResource\Pages;

final class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;
    protected static ?string $navigationIcon = "heroicon-o-truck";
    protected static ?string $navigationGroup = "Taxi Fleet";
    protected static ?string $tenantOwnershipRelationshipName = "tenant";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make("Driver Information")->schema([
                TextInput::make("first_name")->required()->maxLength(255),
                TextInput::make("last_name")->required()->maxLength(255),
                TextInput::make("phone_number")->required()->maxLength(20),
                TextInput::make("license_number")->required()->maxLength(50),
            ])->columns(2),

            Section::make("Status & Location")->schema([
                Checkbox::make("is_active")->default(true),
                Checkbox::make("is_available")->default(true),
                TextInput::make("rating")->numeric()->default(5.0),
                TextInput::make("current_lat")->numeric()->required(),
                TextInput::make("current_lon")->numeric()->required(),
            ])->columns(3),
            
            Section::make("B2B Documents (License, Medical, Insurance)")->schema([
                Repeater::make("documents")
                    ->schema([
                        TextInput::make("doc_type")->required()->placeholder("e.g. License, Med Card"),
                        FileUpload::make("file_url")->acceptedFileTypes(["application/pdf", "image/*"])->directory("taxi/docs")->required(),
                        TextInput::make("expires_at")->type("date"),
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
                TextColumn::make("first_name")->searchable()->sortable(),
                TextColumn::make("last_name")->searchable()->sortable(),
                TextColumn::make("phone_number")->searchable(),
                TextColumn::make("rating")->sortable(),
                BooleanColumn::make("is_active"),
                BooleanColumn::make("is_available"),
            ])
            ->filters([
                TernaryFilter::make("is_active"),
                TernaryFilter::make("is_available"),
            ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListDrivers::route("/"),
            "create" => Pages\CreateDriver::route("/create"),
            "edit" => Pages\EditDriver::route("/{record}/edit"),
        ];
    }
}
