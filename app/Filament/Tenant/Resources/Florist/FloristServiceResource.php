<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Florist;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FloristServiceResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Section, TextInput, Select, RichEditor, FileUpload, Toggle, TagsInput, Hidden, Grid};
    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Columns\{TextColumn, BadgeColumn, BooleanColumn, ImageColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter};
    use Filament\Tables\Actions\{ActionGroup, ViewAction, EditAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class FloristServiceResource extends Resource
    {
        protected static ?string $model = \App\Domains\Florist\Models\FloristService::class;
        protected static ?string $navigationIcon = 'heroicon-o-flower';
        protected static ?string $navigationGroup = 'Florist';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-cube')
                    ->schema([
                        TextInput::make('uuid')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        TextInput::make('florist_shop_id')->label('Флористическая мастерская')->required()->columnSpan(2),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('main_photo')->label('Главное фото')->image()->directory('florist')->columnSpan(1),
                        FileUpload::make('portfolio_photos')->label('Портфолио')->image()->multiple()->directory('florist')->columnSpan(1),
                    ])->columns(4),

                Section::make('Тип услуги')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('service_type')
                            ->label('Тип услуги')
                            ->options([
                                'bouquet' => '💐 Букеты',
                                'arrangement' => '🌸 Композиции',
                                'wedding' => '💒 Свадебные',
                                'funeral' => '⚫ Траурные',
                                'subscription' => '📅 Подписка',
                                'dried_flowers' => '🌿 Сухоцветы',
                                'gift_baskets' => '🎁 Подарочные корзины',
                                'custom' => '✨ Под заказ',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TagsInput::make('flower_types')->label('Виды цветов')->columnSpan('full'),

                        Select::make('style')
                            ->label('Стиль оформления')
                            ->options([
                                'classic' => 'Классический',
                                'modern' => 'Современный',
                                'romantic' => 'Романтичный',
                                'minimalist' => 'Минимализм',
                                'bohemian' => 'Бохо',
                                'japanese' => 'Японский',
                            ])
                            ->columnSpan(2),

                        TextInput::make('color_theme')->label('Цветовая палитра')->columnSpan(2),
                    ])->columns(4),

                Section::make('Цена и условия')
                    ->icon('heroicon-m-banknote')
                    ->schema([
                        TextInput::make('base_price')->label('Базовая цена (₽)')->numeric()->required()->suffix('₽')->columnSpan(2),
                        TextInput::make('min_quantity')->label('Минимум букетов')->numeric()->columnSpan(1),
                        TextInput::make('delivery_radius_km')->label('Радиус доставки (км)')->numeric()->columnSpan(1),
                        TextInput::make('delivery_cost')->label('Стоимость доставки (₽)')->numeric()->columnSpan(2),
                    ])->columns(4),

                Section::make('Время выполнения')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        TextInput::make('preparation_hours')->label('Подготовка (ч)')->numeric()->columnSpan(1),
                        TextInput::make('shelf_life_days')->label('Сохранность (дн)')->numeric()->columnSpan(1),
                        Toggle::make('same_day_delivery')->label('Доставка в день заказа')->columnSpan(2),
                        Toggle::make('available_on_weekends')->label('Работаем выходные')->columnSpan(2),
                    ])->columns(4),

                Section::make('Рейтинг и отзывы')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('review_count')->label('Отзывов')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('completed_orders')->label('Выполнено заказов')->numeric()->disabled()->columnSpan(2),
                    ])->columns(4),

                Section::make('Управление')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Toggle::make('is_active')->label('Активен')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Рекомендуемый')->columnSpan(1),
                        Toggle::make('is_verified')->label('✓ Проверен')->columnSpan(2),
                    ])->columns(4),

                Section::make('Служебная')
                    ->schema([
                        Hidden::make('tenant_id')->default(fn () => tenant('id')),
                        Hidden::make('correlation_id')->default(fn () => Str::uuid()),
                        Hidden::make('business_group_id')->default(fn () => filament()->getTenant()?->active_business_group_id),
                    ]),
            ]);
        }

        public static function table(Tables\Table $table): Tables\Table
        {
            return $table->columns([
                ImageColumn::make('main_photo')->label('Фото')->height(50),
                TextColumn::make('name')->label('Название')->searchable()->sortable()->limit(30),
                BadgeColumn::make('service_type')->label('Тип')->color('info'),
                TextColumn::make('base_price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('rating')->label('⭐')->badge()->color(fn ($state) => $state >= 4.5 ? 'success' : 'warning'),
                TextColumn::make('completed_orders')->label('Заказов')->numeric(),
                BadgeColumn::make('style')->label('Стиль')->color('warning'),
                BooleanColumn::make('same_day_delivery')->label('🚚'),
                BooleanColumn::make('is_verified')->label('✓'),
                BooleanColumn::make('is_active')->label('Активен')->toggleable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('service_type')->label('Тип услуги')->options(['bouquet' => 'Букеты', 'arrangement' => 'Композиции', 'wedding' => 'Свадебные'])->multiple(),
                SelectFilter::make('style')->label('Стиль')->options(['classic' => 'Классический', 'modern' => 'Современный', 'romantic' => 'Романтичный'])->multiple(),
                TernaryFilter::make('same_day_delivery')->label('Доставка в день'),
                TernaryFilter::make('is_verified')->label('Проверен'),
                TrashedFilter::make(),
            ])
            ->actions([ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()])])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('verify')->label('Верифицировать')->action(fn ($records) => $records->each(fn ($r) => $r->update(['is_verified' => true])))->deselectRecordsAfterCompletion()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Florist\FloristServiceResource\Pages\ListFloristServices::route('/'),
                'create' => \App\Filament\Tenant\Resources\Florist\FloristServiceResource\Pages\CreateFloristService::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Florist\FloristServiceResource\Pages\EditFloristService::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}
