<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessGroupResource\Pages;
use App\Models\BusinessGroup;
use App\Enums\BusinessGroupVerificationStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class BusinessGroupResource extends Resource
{
    protected static ?string $model = BusinessGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Бизнес-группы';

    protected static ?string $modelLabel = 'Бизнес-группа';

    protected static ?string $pluralModelLabel = 'Бизнес-группы';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->relationship('tenant', 'name')
                            ->required()
                            ->searchable()
                            ->label('Тенант'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Название'),

                        Forms\Components\TextInput::make('inn')
                            ->required()
                            ->maxLength(12)
                            ->label('ИНН'),

                        Forms\Components\TextInput::make('kpp')
                            ->maxLength(9)
                            ->label('КПП'),

                        Forms\Components\Textarea::make('legal_address')
                            ->label('Юридический адрес')
                            ->rows(2),

                        Forms\Components\Textarea::make('actual_address')
                            ->label('Фактический адрес')
                            ->rows(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Верификация')
                    ->schema([
                        Forms\Components\Select::make('verification_status')
                            ->options(BusinessGroupVerificationStatus::class)
                            ->required()
                            ->label('Статус верификации'),

                        Forms\Components\DateTimePicker::make('inn_verified_at')
                            ->label('Дата верификации ИНН'),

                        Forms\Components\Toggle::make('is_branch')
                            ->label('Является филиалом'),

                        Forms\Components\Select::make('parent_business_group_id')
                            ->relationship('parentBusinessGroup', 'name')
                            ->searchable()
                            ->label('Родительская бизнес-группа'),

                        Forms\Components\Textarea::make('moderator_notes')
                            ->label('Заметки модератора')
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна'),

                        Forms\Components\Toggle::make('is_verified')
                            ->label('Верифицирована'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['tenant', 'parentBusinessGroup']))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('inn')
                    ->label('ИНН')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Тенант')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('verification_status')
                    ->label('Статус верификации')
                    ->formatStateUsing(fn (BusinessGroupVerificationStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (BusinessGroupVerificationStatus $state): string => match ($state) {
                        BusinessGroupVerificationStatus::Approved => 'success',
                        BusinessGroupVerificationStatus::AutoApproved => 'success',
                        BusinessGroupVerificationStatus::Pending => 'warning',
                        BusinessGroupVerificationStatus::ManualReview => 'info',
                        BusinessGroupVerificationStatus::Rejected => 'danger',
                        BusinessGroupVerificationStatus::Suspended => 'danger',
                    }),

                Tables\Columns\IconColumn::make('is_branch')
                    ->label('Филиал')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->options(BusinessGroupVerificationStatus::class)
                    ->label('Статус верификации'),

                Tables\Filters\TernaryFilter::make('is_branch')
                    ->label('Филиалы'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные'),

                Tables\Filters\SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->label('Тенант'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'tenant',
            'parentBusinessGroup',
            'childBusinessGroups',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessGroups::route('/'),
            'create' => Pages\CreateBusinessGroup::route('/create'),
            'view' => Pages\ViewBusinessGroup::route('/{record}'),
            'edit' => Pages\EditBusinessGroup::route('/{record}/edit'),
        ];
    }
}
