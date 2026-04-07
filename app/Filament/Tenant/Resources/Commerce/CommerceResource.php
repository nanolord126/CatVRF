<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Commerce;




use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Models\Commerce;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CommerceResource extends Resource
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    protected static ?string $model = Commerce::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 23;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('store_code')->label('Код магазина')->required()->hidden(),
                TextInput::make('store_name')->label('Название магазина')->required(),
                Select::make('commerce_type')->label('Тип торговли')->options([
                    'retail' => 'Розница','wholesale' => 'Оптом','e-commerce' => 'E-commerce','mixed' => 'Смешанная',
                ])->required(),
                Select::make('category')->label('Категория')->options([
                    'apparel' => 'Одежда','electronics' => 'Электроника','home' => 'Дом','sports' => 'Спорт',
                    'toys' => 'Игрушки','beauty' => 'Красота','food' => 'Еда','books' => 'Книги',
                ])->required(),
                TextInput::make('phone')->label('Телефон')->tel()->required(),
                FileUpload::make('logo')->label('Логотип')->image()->directory('commerce'),
            ]),
            Section::make('Адрес')->columns(2)->schema([
                TextInput::make('physical_address')->label('Адрес')->required()->columnSpanFull(),
                TextInput::make('city')->label('Город')->required(),
                TextInput::make('website')->label('Веб-сайт')->url(),
            ]),
            Section::make('Описание')->schema([
                RichEditor::make('description')->label('Описание')->columnSpanFull(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Section::make('Каталог и товары')->columns(2)->schema([
                TextInput::make('total_products')->label('Всего товаров')->numeric(),
                TextInput::make('unique_brands')->label('Брендов')->numeric(),
                TextInput::make('sku_count')->label('SKU')->numeric(),
                Toggle::make('has_exclusive_products')->label('Эксклюзивные товары'),
            ]),
            Section::make('Инвентарь и складирование')->columns(2)->schema([
                TextInput::make('warehouse_space_sqm')->label('Площадь склада (м²)')->numeric(),
                TextInput::make('inventory_turnover_days')->label('Оборот инвентаря (дни)')->numeric(),
                Toggle::make('offers_pre_order')->label('Предзаказы'),
                Toggle::make('dropshipping_enabled')->label('Dropshipping'),
            ]),
            Section::make('Продажи и доставка')->columns(2)->schema([
                Toggle::make('physical_store_available')->label('Физический магазин'),
                Toggle::make('online_store_available')->label('Онлайн магазин'),
                TextInput::make('shipping_options')->label('Варианты доставки'),
                TextInput::make('avg_shipping_time_days')->label('Средн. время доставки (дн)')->numeric(),
                Toggle::make('free_shipping_available')->label('Бесплатная доставка'),
            ]),
            Section::make('Платежи и возвраты')->columns(2)->schema([
                TagsInput::make('payment_methods')->label('Способы оплаты'),
                TextInput::make('return_policy_days')->label('Период возврата (дни)')->numeric(),
                Toggle::make('money_back_guarantee')->label('Гарантия возврата'),
                TextInput::make('warranty_months')->label('Гарантия (месяцы)')->numeric(),
            ]),
            Section::make('Рейтинг и метрики')->columns(2)->schema([
                TextInput::make('rating')->label('Рейтинг')->numeric()->disabled(),
                TextInput::make('reviews_count')->label('Отзывов')->numeric()->disabled(),
                TextInput::make('monthly_orders')->label('Заказов/месяц')->numeric(),
                TextInput::make('customer_retention_percent')->label('Удержание клиентов %')->numeric(),
            ]),
            Section::make('Скрытые поля')->hidden()->schema([
                TextInput::make('tenant_id')->default(fn()=>filament()->getTenant()->id),
                TextInput::make('correlation_id')->default(fn()=>Str::uuid()->toString()),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('logo')->label('Логотип')->square()->size(40),
            TextColumn::make('store_name')->label('Магазин')->searchable()->sortable(),
            BadgeColumn::make('commerce_type')->label('Тип'),
            BadgeColumn::make('category')->label('Категория'),
            TextColumn::make('total_products')->label('Товаров')->numeric(),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            TextColumn::make('monthly_orders')->label('Заказов/мес'),
            ToggleColumn::make('is_active')->label('Активен'),
        ])->defaultSort('store_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->db->transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            $this->logger->info('Commerce store action',['user'=>$this->guard->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
