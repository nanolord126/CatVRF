<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Furniture;

use App\Domains\Furniture\Models\FurnitureItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class FurnitureResource extends Resource
{
    protected static ?string $model = FurnitureItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Вертикали';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
            Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

            Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Название')->required()->maxLength(255)->columnSpan(2),
                    TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true)->columnSpan(1),
                    TextInput::make('article_number')->label('Артикул')->columnSpan(1),
                    Select::make('type')->label('Тип')->options([
                        'sofa' => 'Диван',
                        'armchair' => 'Кресло',
                        'bed' => 'Кровать',
                        'table' => 'Стол',
                        'chair' => 'Стул',
                        'cabinet' => 'Шкаф',
                        'shelf' => 'Полка',
                        'nightstand' => 'Тумба',
                        'desk' => 'Письменный стол',
                        'bookcase' => 'Шкаф для книг'
                    ])->required()->columnSpan(1),
                    TextInput::make('brand')->label('Бренд')->maxLength(100)->columnSpan(1),
                    Select::make('style')->label('Стиль')->options([
                        'modern' => 'Модерн',
                        'classic' => 'Классика',
                        'scandinavian' => 'Скандинавский',
                        'loft' => 'Лофт',
                        'art_deco' => 'Арт-деко',
                        'minimalism' => 'Минимализм',
                        'provence' => 'Прованс',
                        'vintage' => 'Винтаж'
                    ])->columnSpan(1),
                ]),

            Section::make('Описание')
                ->schema([
                    Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                    RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                ]),

            Section::make('Размеры и характеристики')
                ->collapsed()
                ->columns(3)
                ->schema([
                    TextInput::make('length_cm')->label('Длина (см)')->numeric()->columnSpan(1),
                    TextInput::make('width_cm')->label('Ширина (см)')->numeric()->columnSpan(1),
                    TextInput::make('height_cm')->label('Высота (см)')->numeric()->columnSpan(1),
                    TextInput::make('weight_kg')->label('Вес (кг)')->numeric()->columnSpan(1),
                    TextInput::make('seat_height_cm')->label('Высота сиденья (см)')->numeric()->columnSpan(1),
                    TextInput::make('load_capacity_kg')->label('Грузоподъёмность (кг)')->numeric()->columnSpan(1),
                    Select::make('color')->label('Цвет')->options([
                        'black' => 'Чёрный',
                        'white' => 'Белый',
                        'brown' => 'Коричневый',
                        'gray' => 'Серый',
                        'beige' => 'Бежевый',
                        'blue' => 'Синий',
                        'red' => 'Красный',
                        'natural_wood' => 'Натуральное дерево'
                    ])->columnSpan(1),
                    TagsInput::make('available_colors')->label('Доступные цвета')->columnSpan(2),
                ]),

            Section::make('Материалы')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Select::make('material')->label('Основной материал')->options([
                        'wood' => 'Дерево',
                        'metal' => 'Металл',
                        'fabric' => 'Ткань',
                        'leather' => 'Кожа',
                        'mdf' => 'МДФ',
                        'particle_board' => 'ДСП',
                        'glass' => 'Стекло',
                        'plastic' => 'Пластик',
                        'composite' => 'Композит'
                    ])->columnSpan(1),
                    TextInput::make('material_description')->label('Описание материала')->columnSpan(1),
                    Toggle::make('is_eco_friendly')->label('Эко-материалы')->columnSpan(1),
                    Toggle::make('is_upholstered')->label('Обивка')->columnSpan(1),
                    TextInput::make('upholstery_material')->label('Материал обивки')->columnSpan(1),
                    Select::make('fabric_type')->label('Тип ткани')->options([
                        'cotton' => 'Хлопок',
                        'linen' => 'Лён',
                        'wool' => 'Шерсть',
                        'synthetic' => 'Синтетика',
                        'leather' => 'Кожа',
                        'suede' => 'Замша'
                    ])->columnSpan(1),
                ]),

            Section::make('Сборка и доставка')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Toggle::make('assembly_required')->label('Требуется сборка')->columnSpan(1),
                    TextInput::make('assembly_time_min')->label('Время сборки (мин)')->numeric()->columnSpan(1),
                    TextInput::make('assembly_difficulty')->label('Сложность сборки')->options([
                        'easy' => 'Простая',
                        'medium' => 'Средняя',
                        'hard' => 'Сложная'
                    ])->columnSpan(1),
                    Toggle::make('assembly_service_available')->label('Услуга сборки')->columnSpan(1),
                    TextInput::make('assembly_service_price')->label('Цена сборки (₽)')->numeric()->columnSpan(1),
                    TextInput::make('delivery_days')->label('Срок доставки (дн)')->numeric()->columnSpan(1),
                ]),

            Section::make('Цена и доступность')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('price')->label('Цена (₽)')->numeric()->required()->columnSpan(1),
                    TextInput::make('original_price')->label('Оригинальная цена (₽)')->numeric()->columnSpan(1),
                    TextInput::make('discount_percent')->label('Скидка (%)')->numeric()->columnSpan(1),
                    Toggle::make('in_stock')->label('В наличии')->columnSpan(1),
                    TextInput::make('stock_quantity')->label('Количество')->numeric()->columnSpan(1),
                ]),

            Section::make('Дополнительные опции')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Toggle::make('is_modular')->label('Модульная мебель')->columnSpan(1),
                    Toggle::make('is_transformable')->label('Трансформирующаяся')->columnSpan(1),
                    Toggle::make('has_storage')->label('С хранением')->columnSpan(1),
                    Toggle::make('is_foldable')->label('Складная')->columnSpan(1),
                    Toggle::make('has_adjustable_height')->label('Регулировка высоты')->columnSpan(1),
                    Toggle::make('is_outdoor')->label('Для улицы')->columnSpan(1),
                    TagsInput::make('special_features')->label('Особенности')->columnSpan(2),
                ]),

            Section::make('Гарантия и уход')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('warranty_years')->label('Гарантия (лет)')->numeric()->columnSpan(1),
                    Textarea::make('care_instructions')->label('Инструкции по уходу')->maxLength(1000)->rows(3)->columnSpan(2),
                ]),

            Section::make('Медиа')
                ->collapsed()
                ->schema([
                    FileUpload::make('main_image')->label('Главное фото')->image()->directory('furniture-main'),
                    FileUpload::make('gallery')->label('Галерея 3D')->multiple()->image()->directory('furniture-gallery')->columnSpan('full'),
                    FileUpload::make('assembly_instructions')->label('Инструкция сборки (PDF)')->acceptedFileTypes(['application/pdf'])->directory('furniture-manuals')->columnSpan(1),
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
                    Toggle::make('is_active')->label('Активно')->default(true),
                    Toggle::make('is_featured')->label('Избранное')->default(false),
                    Toggle::make('verified')->label('Проверено')->default(false),
                    TextInput::make('priority')->label('Приоритет')->numeric()->columnSpan(2),
                    DatePicker::make('published_at')->label('Публикация')->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('main_image')->label('Фото')->size(50),
            TextColumn::make('name')->label('Название')->searchable()->sortable()->weight('bold')->limit(40),
            TextColumn::make('type')->label('Тип')->badge()->color('info'),
            TextColumn::make('style')->label('Стиль')->badge()->color('secondary'),
            TextColumn::make('price')->label('Цена (₽)')->numeric()->sortable()->badge()->color('success'),
            BadgeColumn::make('in_stock')->label('В наличии')->colors(['success' => true, 'gray' => false]),
            BadgeColumn::make('assembly_required')->label('Сборка')->colors(['warning' => true, 'gray' => false]),
            BadgeColumn::make('is_eco_friendly')->label('Эко')->colors(['info' => true, 'gray' => false]),
            BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
            TextColumn::make('sku')->label('SKU')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('type')->options([
                'sofa' => 'Диван',
                'armchair' => 'Кресло',
                'bed' => 'Кровать',
                'table' => 'Стол',
                'chair' => 'Стул',
                'cabinet' => 'Шкаф',
                'shelf' => 'Полка',
            ]),
            SelectFilter::make('style')->options([
                'modern' => 'Модерн',
                'classic' => 'Классика',
                'scandinavian' => 'Скандинавский',
                'loft' => 'Лофт',
            ]),
            Filter::make('in_stock')->query(fn (Builder $q) => $q->where('in_stock', true)),
            Filter::make('eco')->query(fn (Builder $q) => $q->where('is_eco_friendly', true))->label('Эко-материалы'),
        ])->actions([ViewAction::make(), EditAction::make()])
        ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
        ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFurniture::route('/'),
            'create' => Pages\CreateFurniture::route('/create'),
            'edit' => Pages\EditFurniture::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
