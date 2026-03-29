<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate;

use App\Domains\RealEstate\Models\Property;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'RealEstate';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')
                ->icon('heroicon-m-home')
                ->description('Описание объекта недвижимости')
                ->schema([
                    Forms\Components\TextInput::make('uuid')
                        ->label('UUID')
                        ->default(fn () => Str::uuid())
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('address')
                        ->label('Адрес')
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->copyable()
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->copyable()
                        ->columnSpan(1),

                    Forms\Components\RichEditor::make('description')
                        ->label('Описание')
                        ->columnSpan('full'),

                    Forms\Components\FileUpload::make('main_photo')
                        ->label('Главное фото')
                        ->image()
                        ->directory('properties')
                        ->columnSpan(1),

                    Forms\Components\FileUpload::make('gallery_photos')
                        ->label('Галерея (360° фото)')
                        ->image()
                        ->multiple()
                        ->directory('properties')
                        ->columnSpan(1),
                ])->columns(4),

            Forms\Components\Section::make('Местоположение')
                ->icon('heroicon-m-map-pin')
                ->description('Географические координаты и район')
                ->schema([
                    Forms\Components\TextInput::make('city')
                        ->label('Город')
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('district')
                        ->label('Район')
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('latitude')
                        ->label('Широта')
                        ->numeric()
                        ->step(0.0001)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('longitude')
                        ->label('Долгота')
                        ->numeric()
                        ->step(0.0001)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('postal_code')
                        ->label('Почтовый индекс')
                        ->columnSpan(2),
                ])->columns(4),

            Forms\Components\Section::make('Характеристики объекта')
                ->icon('heroicon-m-squares-2x2')
                ->description('Основные параметры недвижимости')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Тип объекта')
                        ->options([
                            'apartment' => '🏠 Квартира',
                            'house' => '🏡 Частный дом',
                            'land' => '🌳 Земельный участок',
                            'commercial' => '🏢 Коммерческое помещение',
                            'office' => '🏛️ Офис',
                            'warehouse' => '🏭 Склад',
                            'industrial' => '⚙️ Промышленное',
                        ])
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('area')
                        ->label('Площадь (м²)')
                        ->numeric()
                        ->required()
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('rooms')
                        ->label('Комнаты')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('bathrooms')
                        ->label('Санузлы')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('floor')
                        ->label('Этаж')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('total_floors')
                        ->label('Всего этажей')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(1),

                    Forms\Components\Select::make('renovation')
                        ->label('Ремонт')
                        ->options([
                            'no_repair' => 'Без отделки',
                            'cosmetic' => 'Косметический',
                            'standard' => 'Стандартный',
                            'premium' => 'Премиум',
                            'luxury' => 'Люкс',
                        ])
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('year_built')
                        ->label('Год постройки')
                        ->numeric()
                        ->minValue(1800)
                        ->columnSpan(1),

                    Forms\Components\Select::make('building_type')
                        ->label('Тип здания')
                        ->options([
                            'brick' => 'Кирпич',
                            'panel' => 'Панель',
                            'monolithic' => 'Монолит',
                            'wooden' => 'Дерево',
                        ])
                        ->columnSpan(1),

                    Forms\Components\TagsInput::make('amenities')
                        ->label('Удобства')
                        ->columnSpan('full'),
                ])->columns(4),

            Forms\Components\Section::make('Цена и условия')
                ->icon('heroicon-m-banknote')
                ->description('Стоимость и параметры сделки')
                ->schema([
                    Forms\Components\TextInput::make('price')
                        ->label('Цена (₽)')
                        ->numeric()
                        ->required()
                        ->suffix('₽')
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('price_per_sqm')
                        ->label('Цена за м² (₽)')
                        ->numeric()
                        ->disabled()
                        ->suffix('₽/м²')
                        ->columnSpan(2),

                    Forms\Components\Select::make('property_status')
                        ->label('Статус объекта')
                        ->options([
                            'for_sale' => 'На продажу',
                            'for_rent' => 'В аренду',
                            'sold' => 'Продано',
                            'rented' => 'Сдано в аренду',
                        ])
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('rent_price_monthly')
                        ->label('Стоимость аренды в месяц (₽)')
                        ->numeric()
                        ->suffix('₽')
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('min_lease_months')
                        ->label('Минимальный срок аренды (мес)')
                        ->numeric()
                        ->minValue(1)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('deposit_required')
                        ->label('Залог (месячных)')
                        ->numeric()
                        ->minValue(0)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('commission_percent')
                        ->label('Комиссия агента (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->columnSpan(1),

                    Forms\Components\RichEditor::make('terms')
                        ->label('Условия сделки')
                        ->columnSpan('full'),
                ])->columns(4),

            Forms\Components\Section::make('Статус и управление')
                ->icon('heroicon-m-cog-6-tooth')
                ->description('Публикация и видимость')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true)
                        ->columnSpan(1),

                    Forms\Components\Toggle::make('is_verified')
                        ->label('✓ Проверен')
                        ->columnSpan(1),

                    Forms\Components\Toggle::make('is_featured')
                        ->label('⭐ Рекомендуемый')
                        ->columnSpan(1),

                    Forms\Components\Toggle::make('is_premium')
                        ->label('💎 Премиум')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('rating')
                        ->label('Рейтинг (0-5)')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('review_count')
                        ->label('Количество отзывов')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(2),
                ])->columns(4),

            Forms\Components\Section::make('Служебная информация')
                ->icon('heroicon-m-cog-6-tooth')
                ->schema([
                    Forms\Components\Hidden::make('tenant_id')
                        ->default(fn () => tenant('id')),

                    Forms\Components\Hidden::make('correlation_id')
                        ->default(fn () => Str::uuid()),

                    Forms\Components\Hidden::make('business_group_id')
                        ->default(fn () => filament()->getTenant()?->active_business_group_id),

                    Forms\Components\TextInput::make('created_at')
                        ->label('Создан')
                        ->disabled()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('updated_at')
                        ->label('Обновлён')
                        ->disabled()
                        ->columnSpan(2),
                ])->columns(4),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('address')
                ->label('Адрес')
                ->searchable()
                ->sortable()
                ->icon('heroicon-m-map-pin')
                ->limit(50),

            Tables\Columns\TextColumn::make('city')
                ->label('Город')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('district')
                ->label('Район')
                ->searchable()
                ->limit(30),

            Tables\Columns\BadgeColumn::make('type')
                ->label('Тип')
                ->formatStateUsing(fn ($state) => match($state) {
                    'apartment' => 'Квартира',
                    'house' => 'Дом',
                    'land' => 'Участок',
                    'commercial' => 'Коммерция',
                    'office' => 'Офис',
                    'warehouse' => 'Склад',
                    default => $state,
                })
                ->color(fn ($state) => match($state) {
                    'apartment' => 'blue',
                    'house' => 'green',
                    'land' => 'amber',
                    'commercial' => 'purple',
                    default => 'gray',
                }),

            Tables\Columns\TextColumn::make('area')
                ->label('Площадь (м²)')
                ->numeric()
                ->sortable(),

            Tables\Columns\TextColumn::make('rooms')
                ->label('Комнаты')
                ->numeric()
                ->sortable()
                ->alignment('center'),

            Tables\Columns\TextColumn::make('price')
                ->label('Цена')
                ->money('RUB', divideBy: 100)
                ->sortable(),

            Tables\Columns\TextColumn::make('price_per_sqm')
                ->label('Цена/м²')
                ->money('RUB', divideBy: 100)
                ->sortable(),

            Tables\Columns\BadgeColumn::make('property_status')
                ->label('Статус')
                ->formatStateUsing(fn ($state) => match($state) {
                    'for_sale' => 'На продажу',
                    'for_rent' => 'В аренду',
                    'sold' => 'Продано',
                    'rented' => 'Сдано',
                    default => $state,
                })
                ->color(fn ($state) => match($state) {
                    'for_sale' => 'info',
                    'for_rent' => 'warning',
                    'sold' => 'success',
                    'rented' => 'success',
                    default => 'gray',
                }),

            Tables\Columns\TextColumn::make('rating')
                ->label('Рейтинг')
                ->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))
                ->badge()
                ->color(fn ($state) => match(true) {
                    $state >= 4.5 => 'success',
                    $state >= 4 => 'info',
                    $state >= 3.5 => 'warning',
                    default => 'danger',
                })
                ->sortable(),

            Tables\Columns\TextColumn::make('review_count')
                ->label('Отзывы')
                ->numeric()
                ->alignment('center'),

            Tables\Columns\BooleanColumn::make('is_verified')
                ->label('✓ Проверен')
                ->toggleable()
                ->sortable(),

            Tables\Columns\BooleanColumn::make('is_featured')
                ->label('⭐ Рекомендуемый')
                ->toggleable(),

            Tables\Columns\BooleanColumn::make('is_active')
                ->label('Активен')
                ->toggleable()
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Создан')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Обновлён')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('type')
                ->label('Тип объекта')
                ->options([
                    'apartment' => 'Квартира',
                    'house' => 'Дом',
                    'land' => 'Участок',
                    'commercial' => 'Коммерция',
                    'office' => 'Офис',
                    'warehouse' => 'Склад',
                ])
                ->multiple(),

            Tables\Filters\SelectFilter::make('property_status')
                ->label('Статус')
                ->options([
                    'for_sale' => 'На продажу',
                    'for_rent' => 'В аренду',
                    'sold' => 'Продано',
                    'rented' => 'Сдано в аренду',
                ])
                ->multiple(),

            Tables\Filters\SelectFilter::make('city')
                ->label('Город')
                ->searchable()
                ->preload()
                ->multiple(),

            Tables\Filters\SelectFilter::make('renovation')
                ->label('Ремонт')
                ->options([
                    'no_repair' => 'Без отделки',
                    'cosmetic' => 'Косметический',
                    'standard' => 'Стандартный',
                    'premium' => 'Премиум',
                    'luxury' => 'Люкс',
                ])
                ->multiple(),

            Tables\Filters\TernaryFilter::make('is_verified')
                ->label('Проверен'),

            Tables\Filters\TernaryFilter::make('is_featured')
                ->label('Рекомендуемый'),

            Tables\Filters\TernaryFilter::make('is_active')
                ->label('Активен'),

            Tables\Filters\Filter::make('high_rating')
                ->label('Высокий рейтинг (≥4.0)')
                ->query(fn (Builder $query) => $query->where('rating', '>=', 4.0)),

            Tables\Filters\Filter::make('premium_prices')
                ->label('Премиум (>10M ₽)')
                ->query(fn (Builder $query) => $query->where('price', '>', 1000000000)),

            Tables\Filters\TrashedFilter::make(),
        ])
        ->actions([
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),

                Tables\Actions\Action::make('verify')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->label('Подтвердить')
                    ->visible(fn ($record) => !$record->is_verified)
                    ->action(function ($record) {
                        $record->update(['is_verified' => true]);
                        Log::channel('audit')->info('Property verified', [
                            'property_id' => $record->id,
                            'user_id' => auth()->id(),
                            'correlation_id' => $record->correlation_id,
                        ]);
                    })
                    ->successNotification(),

                Tables\Actions\Action::make('feature')
                    ->icon('heroicon-m-star')
                    ->color('warning')
                    ->label('В рекомендуемые')
                    ->visible(fn ($record) => !$record->is_featured)
                    ->action(function ($record) {
                        $record->update(['is_featured' => true]);
                        Log::channel('audit')->info('Property featured', [
                            'property_id' => $record->id,
                            'user_id' => auth()->id(),
                            'correlation_id' => $record->correlation_id,
                        ]);
                    })
                    ->successNotification(),
            ]),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            Log::channel('audit')->info('Property bulk deleted', [
                                'property_id' => $record->id,
                                'user_id' => auth()->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        });
                    }),

                Tables\Actions\BulkAction::make('activate')
                    ->label('Активировать')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update(['is_active' => true]);
                            Log::channel('audit')->info('Property activated', [
                                'property_id' => $record->id,
                                'user_id' => auth()->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotification(),

                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Деактивировать')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update(['is_active' => false]);
                            Log::channel('audit')->info('Property deactivated', [
                                'property_id' => $record->id,
                                'user_id' => auth()->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotification(),

                Tables\Actions\BulkAction::make('verify')
                    ->label('Подтвердить')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update(['is_verified' => true]);
                            Log::channel('audit')->info('Property bulk verified', [
                                'property_id' => $record->id,
                                'user_id' => auth()->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotification(),
            ]),
        ])
        ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages\ListProperties::route('/'),
            'create' => \App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages\CreateProperty::route('/create'),
            'view' => \App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages\ViewProperty::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages\EditProperty::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}
