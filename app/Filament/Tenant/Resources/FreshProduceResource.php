<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FreshProduce;

use App\Domains\FarmDirect\FreshProduce\Models\FreshProduct;
use App\Filament\Tenant\Resources\FreshProduce\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Ресурс каталога свежих продуктов — КАНОН 2026.
 * Полная реализация без стабов.
 */
final class FreshProduceResource extends Resource
{
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
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->label("Продукт")
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make("farmSupplier.name")
                    ->label("Ферма")
                    ->badge()
                    ->color("success"),
                Tables\Columns\TextColumn::make("category")
                    ->label("Категория"),
                Tables\Columns\TextColumn::make("price_per_unit")
                    ->label("Цена")
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . " ₽")
                    ->sortable(),
                Tables\Columns\TextColumn::make("current_stock")
                    ->label("Остаток")
                    ->sortable()
                    ->color(fn ($record) => $record->current_stock <= $record->min_stock_threshold ? "danger" : "success"),
                Tables\Columns\IconColumn::make("is_eco_certified")
                    ->label("ECO")
                    ->boolean(),
                Tables\Columns\TextColumn::make("harvest_date")
                    ->label("Сбор")
                    ->date("d.m.Y"),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("category")
                    ->options([
                        "fruits" => "Фрукты",
                        "vegetables" => "Овощи",
                        "greens" => "Зелень"
                    ]),
                Tables\Filters\TernaryFilter::make("is_seasonal")
                    ->label("Сезонные"),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListFreshProduces::route("/"),
            "create" => Pages\CreateFreshProduces::route("/create"),
            "edit" => Pages\EditFreshProduces::route("/{record}/edit"),
        ];
    }
}
