<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Beauty\Models\BeautySalon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class BeautyResource extends Resource
{
    protected static ?string $model = BeautySalon::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Салоны красоты';

    protected static ?string $navigationGroup = 'Beauty';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Название'),
            Forms\Components\Textarea::make('address')
                ->required()
                ->maxLength(500)
                ->label('Адрес'),
            Forms\Components\TextInput::make('phone')
                ->tel()
                ->maxLength(20)
                ->label('Телефон'),
            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Активен',
                    'inactive' => 'Неактивен',
                    'pending' => 'Ожидает проверки',
                ])
                ->default('pending')
                ->required()
                ->label('Статус'),
            Forms\Components\Toggle::make('is_verified')
                ->label('Проверен')
                ->default(false),
            Forms\Components\KeyValue::make('schedule_json')
                ->label('Расписание'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название'),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->limit(50)
                    ->label('Адрес'),
                Tables\Columns\TextColumn::make('rating')
                    ->sortable()
                    ->label('Рейтинг'),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Проверен'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending',
                        'danger' => 'inactive',
                    ])
                    ->label('Статус'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Создан'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Активен',
                        'inactive' => 'Неактивен',
                        'pending' => 'Ожидает',
                    ]),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Только проверенные'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);

        // B2B filter: if business_card_id in session
        if (session()->has('business_card_id')) {
            $query->where('business_group_id', session('business_card_id'));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\BeautyResource\Pages\ListBeautySalons::route('/'),
            'create' => \App\Filament\Tenant\Resources\BeautyResource\Pages\CreateBeautySalon::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\BeautyResource\Pages\EditBeautySalon::route('/{record}/edit'),
            'view' => \App\Filament\Tenant\Resources\BeautyResource\Pages\ViewBeautySalon::route('/{record}'),
        ];
    }
}