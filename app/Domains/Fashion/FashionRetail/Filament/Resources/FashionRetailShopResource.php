<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Filament\Resources;

use App\Domains\Fashion\FashionRetail\Models\FashionRetailShop;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

final class FashionRetailShopResource extends Resource
{
    protected static ?string $model = FashionRetailShop::class;

    protected static ?string $navigationGroup = 'Fashion Retail';

    protected static ?string $navigationLabel = 'Shops';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Shop Info')->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('email')->required()->email(),
                TextInput::make('phone')->required(),
                TextInput::make('website')->url()->nullable(),
                Select::make('owner_id')->relationship('owner', 'name')->required(),
            ])->columns(2),

            Section::make('Address & Details')->schema([
                TextInput::make('address')->required(),
                RichEditor::make('description')->columnSpanFull(),
            ]),

            Section::make('Status')->schema([
                BadgeColumn::make('is_verified')->label('Verified'),
                Select::make('is_active')->options([
                    true => 'Active',
                    false => 'Inactive',
                ])->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('owner.name')->searchable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('phone'),
            TextColumn::make('rating')->numeric(),
            BadgeColumn::make('is_verified')->colors(['true' => 'success', 'false' => 'secondary']),
            BadgeColumn::make('is_active')->colors(['true' => 'success', 'false' => 'secondary']),
            TextColumn::make('created_at')->dateTime(),
        ])->filters([])->actions([])->bulkActions([]);
    }
}
