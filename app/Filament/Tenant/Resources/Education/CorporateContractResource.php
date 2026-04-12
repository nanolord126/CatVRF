<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class CorporateContractResource extends Resource
{

    protected static ?string $model = CorporateContract::class;
        protected static ?string $navigationIcon = 'heroicon-o-briefcase';
        protected static ?string $navigationGroup = 'Education (Premium)';
        protected static ?string $navigationLabel = 'B2B Contracts';

        /**
         * Форма создания/редактирования B2B контракта.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Agreement Configuration')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->default(fn () => (string) Str::uuid())
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('contract_number')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Select::make('provider_tenant_id')
                                ->label('Education Provider')
                                ->required()
                                ->options(fn () => \App\Models\Tenant::pluck('name', 'id'))
                                ->searchable(),
                            Forms\Components\TextInput::make('slots_total')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->label('Contract Slots'),
                            Forms\Components\TextInput::make('total_amount_kopecks')
                                ->numeric()
                                ->required()
                                ->label('Cost (in Kopecks)'),
                            Forms\Components\DatePicker::make('expires_at')
                                ->required(),
                        ])->columns(2),
                ]);
        }

        /**
         * Таблица контрактов.
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('contract_number')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('slots_total')
                        ->label('Total Slots')
                        ->numeric(),
                    Tables\Columns\TextColumn::make('slots_available')
                        ->label('Available')
                        ->numeric()
                        ->color(fn (int $state) => $state === 0 ? 'danger' : 'success'),
                    Tables\Columns\TextColumn::make('total_amount_kopecks')
                        ->label('Amount (Kopecks)')
                        ->prefix('₽ ')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'expired' => 'danger',
                            'pending' => 'warning',
                            'closed' => 'gray',
                            default => 'primary',
                        }),
                    Tables\Columns\TextColumn::make('signed_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'active' => 'Active',
                            'expired' => 'Expired',
                            'pending' => 'Pending',
                        ]),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('sign')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->action(fn (CorporateContract $record) => $record->update(['status' => 'active']))
                        ->requiresConfirmation(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        /**
         * Tenant Scoping: B2B контракт виден и Провайдеру, и Клиенту.
         */
        public static function getEloquentQuery(): Builder
        {
            $tenantId = filament()->getTenant()->id;

            return parent::getEloquentQuery()
                ->where(function (Builder $query) use ($tenantId) {
                    $query->where('provider_tenant_id', $tenantId)
                          ->orWhere('client_tenant_id', $tenantId);
                });
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListCorporateContracts::route('/'),
                'create' => Pages\CreateCorporateContract::route('/create'),
                'edit' => Pages\EditCorporateContract::route('/{record}/edit'),
            ];
        }
}
