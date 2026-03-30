<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InsurancePolicyResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = InsurancePolicy::class;

        protected static ?string $navigationIcon = 'heroicon-o-shield-check';
        protected static ?string $navigationGroup = 'Insurance Services';

        protected static ?string $modelLabel = 'Insurance Policy';
        protected static ?string $pluralModelLabel = 'Insurance Policies';

        /**
         * Define the data entry form.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Policy Details')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->default(fn () => (string) Str::uuid())
                                ->disabled()
                                ->dehydrated()
                                ->required(),

                            Forms\Components\Select::make('user_id')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->required()
                                ->label('Client'),

                            Forms\Components\Select::make('type_id')
                                ->options(fn () => InsuranceType::pluck('name', 'id'))
                                ->required()
                                ->label('Insurance Type'),

                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending Coverage',
                                    'active' => 'Policy Active',
                                    'expired' => 'Policy Expired',
                                    'cancelled' => 'Policy Voided',
                                ])
                                ->default('pending')
                                ->required(),

                            Forms\Components\TextInput::make('policy_number')
                                ->default(fn () => 'POL-' . strtoupper(Str::random(10)))
                                ->disabled()
                                ->dehydrated()
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Section::make('Financial Terms')
                        ->schema([
                            Forms\Components\TextInput::make('coverage_amount')
                                ->numeric()
                                ->required()
                                ->label('Coverage (Cents)')
                                ->prefix('RUB'),

                            Forms\Components\TextInput::make('premium_amount')
                                ->numeric()
                                ->required()
                                ->label('Yearly Premium (Cents)')
                                ->prefix('RUB'),
                        ])->columns(2),

                    Forms\Components\Section::make('Risk Data')
                        ->schema([
                            Forms\Components\KeyValue::make('policy_data')
                                ->required()
                                ->label('Structured Policy Data (JSONB)'),

                            Forms\Components\DateTimePicker::make('activated_at')
                                ->label('Activation Date'),

                            Forms\Components\DateTimePicker::make('expires_at')
                                ->required()
                                ->label('Expiration Date'),
                        ]),
                ]);
        }

        /**
         * Define the table view.
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('policy_number')
                        ->searchable()
                        ->sortable()
                        ->copyable()
                        ->label('Policy #'),

                    Tables\Columns\TextColumn::make('user.name')
                        ->label('Client')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('type.name')
                        ->badge()
                        ->color('primary'),

                    Tables\Columns\TextColumn::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'active' => 'success',
                            'pending' => 'warning',
                            'cancelled', 'expired' => 'danger',
                            default => 'gray',
                        }),

                    Tables\Columns\TextColumn::make('coverage_amount')
                        ->money('RUB')
                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total Coverage'))
                        ->label('Coverage'),

                    Tables\Columns\TextColumn::make('premium_amount')
                        ->money('RUB')
                        ->label('Premium'),

                    Tables\Columns\TextColumn::make('expires_at')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'active' => 'Active',
                            'pending' => 'Pending',
                            'expired' => 'Expired',
                        ]),
                    Tables\Filters\SelectFilter::make('type_id')
                        ->relationship('type', 'name'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]);
        }

        /**
         * Restrict query by tenant scoping (Canon 2026).
         */
        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([
                    // If soft deletes were here
                ])
                ->where('tenant_id', tenant()->id ?? 0); // Forced scoping
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Insurance\InsurancePolicyResource\Pages\ListInsurancePolicies::route('/'),
                'create' => \App\Filament\Tenant\Resources\Insurance\InsurancePolicyResource\Pages\CreateInsurancePolicy::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Insurance\InsurancePolicyResource\Pages\EditInsurancePolicy::route('/{record}/edit'),
            ];
        }
}
