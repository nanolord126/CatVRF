<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

use Illuminate\Database\Eloquent\Builder;
final class PartyStoreResource extends Resource
{

    protected static ?string $model = PartyStore::class;

        protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
        protected static ?string $navigationGroup = 'Party Supplies';
        protected static ?string $modelLabel = 'Party Shop';
        protected static ?string $pluralModelLabel = 'Party Shops';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Store Basics')
                        ->description('General store information.')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('description')
                                ->rows(3)
                                ->columnSpanFull(),
                            TextInput::make('address')
                                ->required()
                                ->maxLength(500),
                            Toggle::make('is_active')
                                ->default(true)
                                ->label('Store Active'),
                        ]),

                    Section::make('Contact & Meta')
                        ->description('Contact info and technical metadata.')
                        ->schema([
                            KeyValue::make('contact_info')
                                ->keyLabel('Channel')
                                ->valueLabel('Value'),
                            KeyValue::make('metadata')
                                ->keyLabel('Setting')
                                ->valueLabel('Value'),
                        ])->columns(1),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('address')
                        ->limit(50),
                    TextColumn::make('rating')
                        ->numeric(1)
                        ->sortable(),
                    BooleanColumn::make('is_active'),
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    // (Optional) Add tenant-aware filters here
                ])
                ->actions([
                    EditAction::make(),
                    DeleteAction::make(),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([
                    // Add scopes to disable if needed
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages\ListPartyStores::route('/'),
                'create' => \App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages\CreatePartyStore::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Party\PartyStoreResource\Pages\EditPartyStore::route('/{record}/edit'),
            ];
        }
}
