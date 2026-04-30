<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\TenantVerificationStatus;
use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

final class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->options([
                                'business' => 'Business',
                                'individual' => 'Individual',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Legal Information')
                    ->schema([
                        Forms\Components\TextInput::make('inn')
                            ->label('INN')
                            ->required()
                            ->length(10)
                            ->length(12),
                        Forms\Components\TextInput::make('kpp')
                            ->label('KPP')
                            ->nullable()
                            ->length(9),
                        Forms\Components\TextInput::make('ogrn')
                            ->label('OGRN')
                            ->nullable()
                            ->length(13)
                            ->length(15),
                        Forms\Components\Select::make('legal_entity_type')
                            ->options([
                                'OOO' => 'ООО',
                                'IP' => 'ИП',
                                'AO' => 'АО',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->nullable(),
                        Forms\Components\TextInput::make('timezone')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Addresses')
                    ->schema([
                        Forms\Components\Textarea::make('legal_address')
                            ->nullable()
                            ->rows(2),
                        Forms\Components\Textarea::make('actual_address')
                            ->nullable()
                            ->rows(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Verification')
                    ->schema([
                        Forms\Components\Select::make('verification_status')
                            ->options(collect(TenantVerificationStatus::cases())->pluck('label', 'value'))
                            ->required(),
                        Forms\Components\DateTimePicker::make('verified_at')
                            ->nullable(),
                        Forms\Components\Textarea::make('moderator_notes')
                            ->nullable()
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_verified')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->select(['id', 'name', 'slug', 'inn', 'kpp', 'ogrn', 'phone', 'email', 'website', 'timezone', 'verification_status', 'verified_at', 'is_active', 'is_verified', 'created_at', 'updated_at']))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inn')
                    ->label('INN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('verification_status')
                    ->colors([
                        'warning' => TenantVerificationStatus::Pending,
                        'success' => TenantVerificationStatus::Approved,
                        'success' => TenantVerificationStatus::AutoApproved,
                        'danger' => TenantVerificationStatus::Rejected,
                        'info' => TenantVerificationStatus::ManualReview,
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->options(collect(TenantVerificationStatus::cases())->pluck('label', 'value')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Tenant')
                    ->modalDescription('Are you sure you want to approve this tenant? This will activate their account.')
                    ->visible(fn (Tenant $tenant) => in_array($tenant->verification_status?->value, ['pending', 'manual_review'], true) && Auth::user()?->role?->value === 'super_admin')
                    ->action(function (Tenant $tenant) {
                        $tenant->approveVerification('Approved via Filament by '.Auth::user()?->name);
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3)
                            ->helperText('Provide a clear reason for rejection. This will be visible to the tenant.'),
                    ])
                    ->modalHeading('Reject Tenant')
                    ->visible(fn (Tenant $tenant) => in_array($tenant->verification_status?->value, ['pending', 'manual_review'], true) && Auth::user()?->role?->value === 'super_admin')
                    ->action(function (Tenant $tenant, array $data) {
                        $tenant->rejectVerification($data['reason']);
                    }),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Suspension Reason')
                            ->required()
                            ->rows(3)
                            ->helperText('Provide a clear reason for suspension. This will be visible to the tenant.'),
                    ])
                    ->modalHeading('Suspend Tenant')
                    ->visible(fn (Tenant $tenant) => $tenant->verification_status?->value === 'approved' && Auth::user()?->role?->value === 'super_admin')
                    ->action(function (Tenant $tenant, array $data) {
                        $tenant->suspend($data['reason']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'users' => Tables\Columns\TextColumn::make('users.name'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
