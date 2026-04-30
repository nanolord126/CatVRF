<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\B2B\Resources;

use App\Domains\Supermarket\Models\B2BCompany;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class B2BCompanyResource extends Resource
{
    protected static ?string $model = B2BCompany::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Профиль компании';

    protected static ?string $modelLabel = 'Компания';

    protected static ?string $pluralModelLabel = 'Компании';

    protected static ?string $navigationGroup = 'Настройки компании';

    public static function canViewAny(): bool
    {
        // Only show own company profile
        return true;
    }

    public static function canCreate(): bool
    {
        // Can only create if no company exists
        return !B2BCompany::where('user_id', auth()->id())->exists();
    }

    public static function canDelete($record): bool
    {
        return false; // Cannot delete company
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Название компании')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('inn')
                            ->label('ИНН')
                            ->required()
                            ->length(10)
                            ->numeric()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('kpp')
                            ->label('КПП')
                            ->length(9)
                            ->numeric(),
                        Forms\Components\Textarea::make('legal_address')
                            ->label('Юридический адрес')
                            ->required()
                            ->rows(2),
                        Forms\Components\Textarea::make('actual_address')
                            ->label('Фактический адрес')
                            ->rows(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Контактная информация')
                    ->schema([
                        Forms\Components\TextInput::make('contact_person')
                            ->label('Контактное лицо')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Телефон')
                            ->required()
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'На рассмотрении',
                                'approved' => 'Подтверждена',
                                'rejected' => 'Отклонена',
                            ])
                            ->disabled()
                            ->default('pending'),
                                                Forms\Components\Textarea::make('rejection_reason')
                            ->label('Причина отклонения')
                            ->disabled()
                            ->visible(fn ($get) => $get('status') === 'rejected'),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Заметки администратора')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->visible(fn ($context) => $context === 'view'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inn')
                    ->label('ИНН')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('discount_level')
                    ->label('Уровень скидки')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Контакт')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Зарегистрирован')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Подтверждён')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'На рассмотрении',
                        'approved' => 'Подтверждена',
                        'rejected' => 'Отклонена',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => \Filament\Resources\Pages\ListRecords::route('/'),
            'create' => \Filament\Resources\Pages\CreateRecord::route('/create'),
            'view' => \Filament\Resources\Pages\ViewRecord::route('/{record}'),
            'edit' => \Filament\Resources\Pages\EditRecord::route('/{record}/edit'),
        ];
    }
}
