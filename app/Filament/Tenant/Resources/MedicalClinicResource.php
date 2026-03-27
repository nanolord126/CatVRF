<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Medical\Models\MedicalClinic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * РЕЖИМ ЛЮТЫЙ 2026: MEDICAL CLINIC RESOURCE
 */
final class MedicalClinicResource extends Resource
{
    protected static ?string $model = MedicalClinic::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Medical Platform';
    protected static ?string $slug = 'medical-clinics';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('General Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('license_number')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('description')
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Location & Contact')
                ->schema([
                    Forms\Components\TextInput::make('address')
                        ->required(),
                    Forms\Components\TextInput::make('phone')
                        ->tel(),
                    Forms\Components\TextInput::make('email')
                        ->email(),
                ])->columns(3),

            Forms\Components\Section::make('Settings')
                ->schema([
                    Forms\Components\TagsInput::make('specializations')
                        ->required(),
                    Forms\Components\KeyValue::make('schedule')
                        ->required(),
                    Forms\Components\Toggle::make('is_verified')
                        ->label('Verified by Platform')
                        ->disabled(!auth()->user()->is_admin),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->limit(30),
                Tables\Columns\BadgeColumn::make('rating')
                    ->sortable()
                    ->colors([
                        'danger' => static fn ($state): bool => $state < 3,
                        'warning' => static fn ($state): bool => $state >= 3 && $state < 4,
                        'success' => static fn ($state): bool => $state >= 4,
                    ]),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // DoctorsRelationManager, ServicesRelationManager
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\MedicalClinicResource\Pages\ListMedicalClinics::route('/'),
            'create' => \App\Filament\Tenant\Resources\MedicalClinicResource\Pages\CreateMedicalClinic::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\MedicalClinicResource\Pages\EditMedicalClinic::route('/{record}/edit'),
        ];
    }
}
