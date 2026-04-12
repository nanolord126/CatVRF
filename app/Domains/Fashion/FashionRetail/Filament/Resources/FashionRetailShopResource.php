<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Filament\Resources;

use Carbon\Carbon;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

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
                    TextColumn::make('is_verified')->label('Verified'),
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
                TextColumn::make('is_verified')->badge(),
                TextColumn::make('is_active')->badge(),
                TextColumn::make('created_at')->dateTime(),
            ])->filters([])->actions([])->bulkActions([]);
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
