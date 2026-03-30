<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreshProduceResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = FreshProduct::class;

        protected static ?string $navigationIcon = "heroicon-o-shopping-bag";

        protected static ?string $navigationGroup = "Fresh Produce Market";

        protected static ?string $modelLabel = "Свежий продукт";

        protected static ?string $pluralModelLabel = "Каталог продуктов";

        protected static ?int $navigationSort = 1;

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make("Основная информация")
                    ->description("Базовые параметры продукта и поставщика")
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make("name")
                                ->label("Название продукта")
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2)
                                ->placeholder("Например: Яблоки Голден"),
                            Forms\Components\TextInput::make("sku")
                                ->label("Артикул / SKU")
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50)
                                ->placeholder("FP-001"),
                        ]),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make("farm_supplier_id")
                                ->label("Поставщик (Ферма)")
                                ->relationship("farmSupplier", "name")
                                ->searchable()
                                ->preload()
                                ->required(),
                            Forms\Components\Select::make("category")
                                ->label("Категория")
                                ->required()
                                ->options([
                                    "fruits" => "Фрукты",
                                    "vegetables" => "Овощи",
                                    "greens" => "Зелень",
                                    "berries" => "Ягоды",
                                    "exotic" => "Экзотика",
                                    "roots" => "Корнеплоды"
                                ]),
                            Forms\Components\TextInput::make("unit")
                                ->label("Единица измерения")
                                ->placeholder("кг, шт, пучок")
                                ->required(),
                        ]),
                    ]),

                Forms\Components\Section::make("Ценообразование и склад")
                    ->description("Управление ценами, остатками и порогами уведомлений")
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make("price_per_unit")
                                ->label("Цена за единицу (копейки)")
                                ->numeric()
                                ->required()
                                ->helperText("Сумма в копейках для точности"),
                            Forms\Components\TextInput::make("current_stock")
                                ->label("Текущий остаток")
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make("min_stock_threshold")
                                ->label("Минимальный порог")
                                ->numeric()
                                ->default(10)
                                ->helperText("При достижении этого значения придет уведомление"),
                        ]),
                    ]),

                Forms\Components\Section::make("Характеристики свежести")
                    ->description("Параметры контроля качества и сезонности")
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make("harvest_date")
                                ->label("Дата сбора")
                                ->required(),
                            Forms\Components\TextInput::make("expiry_days")
                                ->label("Срок годности (дней)")
                                ->numeric()
                                ->required(),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make("is_seasonal")
                                ->label("Сезонный продукт")
                                ->live(),
                            Forms\Components\CheckboxList::make("season_months")
                                ->label("Месяцы сезона")
                                ->options([
                                    1 => "Январь", 2 => "Февраль", 3 => "Март",
                                    4 => "Апрель", 5 => "Май", 6 => "Июнь",
                                    7 => "Июль", 8 => "Август", 9 => "Сентябрь",
                                    10 => "Октябрь", 11 => "Ноябрь", 12 => "Декабрь"
                                ])
                                ->columns(4)
                                ->visible(fn (callable $get) => $get("is_seasonal")),
                        ]),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Toggle::make("is_eco_certified")
                                ->label("Эко-сертификат")
                                ->live(),
                            Forms\Components\TextInput::make("eco_certificate_number")
                                ->label("Номер сертификата")
                                ->visible(fn (callable $get) => $get("is_eco_certified"))
                                ->required(fn (callable $get) => $get("is_eco_certified"))
                                ->maxLength(100),
                            Forms\Components\FileUpload::make("certificate_photo")
                                ->label("Фото сертификата")
                                ->image()
                                ->directory("fresh-produce/certificates")
                                ->visible(fn (callable $get) => $get("is_eco_certified")),
                        ]),
                    ]),

                Forms\Components\Section::make("Контент и теги")
                    ->schema([
                        Forms\Components\RichEditor::make("description")
                            ->label("Описание для витрины")
                            ->columnSpanFull()
                            ->placeholder("Расскажите покупателям о вкусе и пользе..."),
                        Forms\Components\TagsInput::make("tags")
                            ->label("Теги для аналитики и поиска")
                            ->placeholder("organic, local, premium"),
                    ]),
            ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFreshProduce::route('/'),
                'create' => Pages\\CreateFreshProduce::route('/create'),
                'edit' => Pages\\EditFreshProduce::route('/{record}/edit'),
                'view' => Pages\\ViewFreshProduce::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFreshProduce::route('/'),
                'create' => Pages\\CreateFreshProduce::route('/create'),
                'edit' => Pages\\EditFreshProduce::route('/{record}/edit'),
                'view' => Pages\\ViewFreshProduce::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFreshProduce::route('/'),
                'create' => Pages\\CreateFreshProduce::route('/create'),
                'edit' => Pages\\EditFreshProduce::route('/{record}/edit'),
                'view' => Pages\\ViewFreshProduce::route('/{record}'),
            ];
        }
}
