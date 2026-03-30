<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Gifts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GiftsResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = GiftProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-gift';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название подарка')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true)->columnSpan(1),
                        Select::make('occasion')->label('Повод')->options([
                            'birthday' => 'День рождения',
                            'anniversary' => 'Годовщина',
                            'wedding' => 'Свадьба',
                            'new_year' => 'Новый год',
                            'valentine' => 'День святого Валентина',
                            'graduation' => 'Выпускной',
                            'corporate' => 'Корпоративный',
                            'other' => 'Другое'
                        ])->required()->columnSpan(1),
                        Select::make('category')->label('Категория')->options([
                            'accessories' => 'Аксессуары',
                            'beauty' => 'Красота',
                            'home' => 'Дом',
                            'tech' => 'Техника',
                            'experience' => 'Впечатления',
                            'food' => 'Продукты',
                            'books' => 'Книги',
                            'jewelry' => 'Украшения'
                        ])->required()->columnSpan(1),
                    ]),

                Section::make('Описание')
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                    ]),

                Section::make('Цена и доступность')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->columnSpan(1),
                        TextInput::make('discount_percent')->label('Скидка (%)')->numeric()->columnSpan(1),
                        TextInput::make('original_price')->label('Оригинальная цена (₽)')->numeric()->columnSpan(1),
                        Toggle::make('in_stock')->label('В наличии')->columnSpan(1),
                        TextInput::make('stock_quantity')->label('Количество')->numeric()->columnSpan(1),
                        TextInput::make('warehouse_quantity')->label('На складе')->numeric()->columnSpan(1),
                    ]),

                Section::make('Характеристики')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('brand')->label('Бренд')->maxLength(255)->columnSpan(1),
                        TextInput::make('material')->label('Материал')->columnSpan(1),
                        TextInput::make('color')->label('Цвет')->columnSpan(1),
                        TextInput::make('size')->label('Размер')->columnSpan(1),
                        TextInput::make('weight_grams')->label('Вес (г)')->numeric()->columnSpan(1),
                        TagsInput::make('features')->label('Особенности')->columnSpan(2),
                    ]),

                Section::make('Упаковка и доставка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_gift_wrapping')->label('Подарочная упаковка')->columnSpan(1),
                        TextInput::make('wrapping_cost')->label('Цена упаковки (₽)')->numeric()->columnSpan(1),
                        Toggle::make('has_greeting_card')->label('Открытка в подарок')->columnSpan(1),
                        Toggle::make('has_free_shipping')->label('Бесплатная доставка')->columnSpan(1),
                        TextInput::make('shipping_days')->label('Срок доставки (дн)')->numeric()->columnSpan(1),
                        TextInput::make('box_dimensions')->label('Размер упаковки (см)')->columnSpan(1),
                    ]),

                Section::make('Рекомендации')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Select::make('recipient_age')->label('Возраст получателя')->options([
                            'kids' => 'Дети (0-12)',
                            'teens' => 'Подростки (13-19)',
                            'adults' => 'Взрослые (20-40)',
                            'senior' => 'Пожилые (40+)',
                            'all' => 'Для всех'
                        ])->multiple()->columnSpan(2),
                        TextInput::make('gender')->label('Пол (M/F/Unisex)')->maxLength(10)->columnSpan(1),
                        TagsInput::make('interests')->label('Интересы')->columnSpan(2),
                    ]),

                Section::make('Персонализация')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('allows_personalization')->label('Допускает персонализацию')->columnSpan(2),
                        TextInput::make('personalization_cost')->label('Цена персонализации (₽)')->numeric()->columnSpan(1),
                        TextInput::make('personalization_time')->label('Время персонализации (дн)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('main_image')->label('Главное фото')->image()->directory('gifts-main'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('gifts-gallery')->columnSpan('full'),
                    ]),

                Section::make('SEO')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('meta_title')->label('Meta Title')->maxLength(60),
                        Textarea::make('meta_description')->label('Meta Description')->maxLength(160)->rows(2)->columnSpan(2),
                        TagsInput::make('meta_keywords')->label('Meta Keywords')->columnSpan(2),
                    ]),

                Section::make('Управление')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label('Активен')->default(true),
                        Toggle::make('is_featured')->label('Избранный')->default(false),
                        Toggle::make('verified')->label('Проверен')->default(false),
                        TextInput::make('priority')->label('Приоритет')->numeric()->columnSpan(2),
                        DatePicker::make('published_at')->label('Публикация')->columnSpan(1),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                ImageColumn::make('main_image')->label('Фото')->size(50),
                TextColumn::make('name')->label('Подарок')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('occasion')->label('Повод')->badge()->color('secondary'),
                TextColumn::make('category')->label('Категория')->badge()->color('info'),
                TextColumn::make('price')->label('Цена (₽)')->numeric()->sortable()->badge()->color('success'),
                BadgeColumn::make('in_stock')->label('В наличии')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('has_gift_wrapping')->label('Упаковка')->colors(['secondary' => true, 'gray' => false]),
                BadgeColumn::make('has_greeting_card')->label('Открытка')->colors(['secondary' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранный')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('stock_quantity')->label('Кол-во')->numeric()->badge()->color('secondary'),
            ])->filters([
                SelectFilter::make('occasion')->options([
                    'birthday' => 'День рождения',
                    'anniversary' => 'Годовщина',
                    'wedding' => 'Свадьба',
                    'new_year' => 'Новый год',
                    'valentine' => 'День святого Валентина',
                    'graduation' => 'Выпускной',
                    'corporate' => 'Корпоративный',
                    'other' => 'Другое'
                ]),
                SelectFilter::make('category')->options([
                    'accessories' => 'Аксессуары',
                    'beauty' => 'Красота',
                    'home' => 'Дом',
                    'tech' => 'Техника',
                    'experience' => 'Впечатления',
                    'food' => 'Продукты',
                    'books' => 'Книги',
                    'jewelry' => 'Украшения'
                ]),
                Filter::make('in_stock')->query(fn (Builder $q) => $q->where('in_stock', true)),
                Filter::make('has_wrapping')->query(fn (Builder $q) => $q->where('has_gift_wrapping', true))->label('С упаковкой'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListGifts::route('/'),
                'create' => Pages\CreateGifts::route('/create'),
                'edit' => Pages\EditGifts::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
