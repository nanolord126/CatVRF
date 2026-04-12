<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class CourierResource extends Resource
{

    protected static ?string $model = Courier::class;

        protected static ?string $navigationIcon = 'heroicon-o-truck';
        protected static ?string $navigationGroup = 'Logistics & Fleet';
        protected static ?int $navigationSort = 1;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Main Info')
                        ->schema([
                            Forms\Components\TextInput::make('full_name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->required()
                                ->unique(ignoreRecord: true),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'offline' => 'Offline',
                                    'online' => 'Online',
                                    'busy' => 'Busy',
                                    'blocked' => 'Blocked',
                                ])
                                ->required()
                                ->default('offline'),
                            Forms\Components\Select::make('vehicle_id')
                                ->label('Assigned Vehicle')
                                ->relationship('vehicle', 'license_plate')
                                ->searchable()
                                ->nullable(),
                            Forms\Components\Toggle::make('is_active')
                                ->default(true),
                        ])->columns(2),

                    Forms\Components\Section::make('Location & Performance')
                        ->schema([
                            Forms\Components\TextInput::make('last_lat')->numeric()->readOnly(),
                            Forms\Components\TextInput::make('last_lon')->numeric()->readOnly(),
                            Forms\Components\TextInput::make('rating')
                                ->numeric()
                                ->step(0.1)
                                ->default(5.0),
                        ])->columns(3),

                    Forms\Components\Section::make('System & Tags')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->default(\Illuminate\Support\Str::uuid()->toString())
                                ->readOnly(),
                            Forms\Components\KeyValue::make('tags')
                                ->keyLabel('Tag Name')
                                ->valueLabel('Tag Value')
                                ->nullable(),
                        ])->collapsed(),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('full_name')
                        ->label('Courier')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('phone')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('vehicle.license_plate')
                        ->label('Vehicle')
                        ->placeholder('No vehicle'),
                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'danger' => 'blocked',
                            'warning' => 'busy',
                            'success' => 'online',
                            'gray' => 'offline',
                        ]),
                    Tables\Columns\TextColumn::make('rating')
                        ->sortable()
                        ->badge(),
                    Tables\Columns\IconColumn::make('is_active')
                        ->boolean(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status'),
                    Tables\Filters\TernaryFilter::make('is_active'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('block')
                        ->color('danger')
                        ->icon('heroicon-o-lock-closed')
                        ->requiresConfirmation()
                        ->action(fn (Courier $record) => $record->update(['status' => 'blocked']))
                        ->visible(fn (Courier $record) => $record->status !== 'blocked'),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->with(["user", "vehicle"])
                ->orderBy("id", "desc");
        }

        public static function getPages(): array
        {
            return [
                "index" => Pages\ListCouriers::route("/"),
                "create" => Pages\CreateCourier::route("/create"),
                "edit" => Pages\EditCourier::route("/{record}/edit"),
            ];
        }
}
