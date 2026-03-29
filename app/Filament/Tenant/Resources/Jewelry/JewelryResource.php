<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Jewelry;

use App\Domains\Jewelry\Models\JewelryItem;
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

final class JewelryResource extends Resource
{
    protected static ?string $model = JewelryItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
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
                    TextInput::make('reference_number')->label('Артикул')->columnSpan(1),
                    Select::make('type')->label('Тип')->options([
                        'ring' => 'Кольцо',
                        'necklace' => 'Ожерелье',
                        'bracelet' => 'Браслет',
                        'earrings' => 'Серьги',
                        'pendant' => 'Подвеска',
                        'brooch' => 'Брошь',
                        'anklet' => 'Браслет на ногу',
                        'body_jewelry' => 'Боди-арт'
                    ])->required()->columnSpan(1),
                    TextInput::make('brand')->label('Бренд')->maxLength(100)->columnSpan(1),
                ]),

            Section::make('Описание')
                ->schema([
                    Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                    RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                ]),

            Section::make('Материалы и камни')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Select::make('metal')->label('Металл')->options([
                        'gold' => 'Золото',
                        'silver' => 'Серебро',
                        'platinum' => 'Платина',
                        'copper' => 'Медь',
                        'bronze' => 'Бронза',
                        'stainless_steel' => 'Нержавейка',
                        'titanium' => 'Титан',
                        'other' => 'Другое'
                    ])->columnSpan(1),
                    TextInput::make('metal_purity')->label('Проба')->columnSpan(1),
                    TextInput::make('metal_weight')->label('Вес металла (г)')->numeric()->columnSpan(1),
                    TextInput::make('total_weight')->label('Общий вес (г)')->numeric()->columnSpan(1),
                    TagsInput::make('stones')->label('Камни')->columnSpan(2),
                    TextInput::make('stone_count')->label('Количество камней')->numeric()->columnSpan(1),
                    TextInput::make('stone_carat')->label('Карат (для алмазов)')->numeric()->columnSpan(1),
                ]),

            Section::make('Размеры и характеристики')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('ring_size')->label('Размер кольца (RU)')->columnSpan(1),
                    TextInput::make('length_cm')->label('Длина (см)')->numeric()->columnSpan(1),
                    TextInput::make('width_mm')->label('Ширина (мм)')->numeric()->columnSpan(1),
                    TextInput::make('thickness_mm')->label('Толщина (мм)')->numeric()->columnSpan(1),
                    TextInput::make('color')->label('Цвет')->columnSpan(1),
                    TagsInput::make('features')->label('Особенности')->columnSpan(2),
                ]),

            Section::make('Сертификаты и подлинность')
                ->collapsed()
                ->columns(2)
                ->schema([
                    FileUpload::make('certificate')->label('Сертификат GIA/IGI (PDF)')->acceptedFileTypes(['application/pdf'])->directory('jewelry-certs')->columnSpan(1),
                    TextInput::make('certificate_number')->label('Номер сертификата')->columnSpan(1),
                    Toggle::make('is_authentic')->label('Проверена подлинность')->columnSpan(1),
                    Toggle::make('has_warranty')->label('Гарантия')->columnSpan(1),
                    TextInput::make('warranty_years')->label('Гарантия (лет)')->numeric()->columnSpan(1),
                    TextInput::make('certificate_issuer')->label('Эмитент')->columnSpan(1),
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

            Section::make('Страховка и доставка')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Toggle::make('has_insurance')->label('Страховка')->columnSpan(1),
                    TextInput::make('insurance_value')->label('Стоимость страховки (₽)')->numeric()->columnSpan(1),
                    Toggle::make('has_gift_box')->label('Подарочная коробка')->columnSpan(1),
                    TextInput::make('shipping_days')->label('Срок доставки (дн)')->numeric()->columnSpan(1),
                ]),

            Section::make('Дизайнер/Производитель')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('designer')->label('Дизайнер')->maxLength(255)->columnSpan(1),
                    TextInput::make('collection_name')->label('Коллекция')->maxLength(255)->columnSpan(1),
                    DatePicker::make('design_year')->label('Год дизайна')->columnSpan(1),
                    TextInput::make('production_country')->label('Страна производства')->columnSpan(1),
                ]),

            Section::make('Медиа')
                ->collapsed()
                ->schema([
                    FileUpload::make('main_image')->label('Главное фото')->image()->directory('jewelry-main'),
                    FileUpload::make('gallery')->label('3D галерея')->multiple()->image()->directory('jewelry-gallery')->columnSpan('full'),
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
            TextColumn::make('metal')->label('Металл')->badge()->color('secondary'),
            TextColumn::make('price')->label('Цена (₽)')->numeric()->sortable()->badge()->color('success'),
            BadgeColumn::make('in_stock')->label('В наличии')->colors(['success' => true, 'gray' => false]),
            BadgeColumn::make('is_authentic')->label('Подлинная')->colors(['info' => true, 'gray' => false]),
            BadgeColumn::make('has_warranty')->label('Гарантия')->colors(['secondary' => true, 'gray' => false]),
            BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
            TextColumn::make('sku')->label('SKU')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('type')->options([
                'ring' => 'Кольцо',
                'necklace' => 'Ожерелье',
                'bracelet' => 'Браслет',
                'earrings' => 'Серьги',
                'pendant' => 'Подвеска',
                'brooch' => 'Брошь',
                'anklet' => 'Браслет на ногу',
                'body_jewelry' => 'Боди-арт'
            ]),
            SelectFilter::make('metal')->options([
                'gold' => 'Золото',
                'silver' => 'Серебро',
                'platinum' => 'Платина',
                'copper' => 'Медь',
                'bronze' => 'Бронза',
                'stainless_steel' => 'Нержавейка',
                'titanium' => 'Титан',
            ]),
            Filter::make('in_stock')->query(fn (Builder $q) => $q->where('in_stock', true)),
            Filter::make('authentic')->query(fn (Builder $q) => $q->where('is_authentic', true))->label('Проверенные'),
        ])->actions([ViewAction::make(), EditAction::make()])
        ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
        ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJewelry::route('/'),
            'create' => Pages\CreateJewelry::route('/create'),
            'edit' => Pages\EditJewelry::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
