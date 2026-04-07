<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Marketplace;




use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Models\Marketplace;
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

final class MarketplaceResource extends Resource
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    protected static ?string $model = Marketplace::class;
    protected static ?string $navigationIcon = 'heroicon-o-window';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('marketplace_code')->label('Код')->required()->hidden(),
                TextInput::make('marketplace_name')->label('Название маркетплейса')->required(),
                Select::make('marketplace_type')->label('Тип')->options([
                    'general' => 'Общий','vertical' => 'Вертикальный','niche' => 'Ниша','regional' => 'Региональный',
                ])->required(),
                TextInput::make('phone')->label('Телефон')->tel()->required(),
                TextInput::make('email')->label('Email')->email()->required(),
                FileUpload::make('logo')->label('Логотип')->image()->directory('marketplace'),
            ]),
            Section::make('Описание')->schema([
                RichEditor::make('description')->label('Описание')->columnSpanFull(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Section::make('Платформа и технология')->columns(2)->schema([
                TextInput::make('website')->label('Веб-сайт')->url(),
                Toggle::make('has_mobile_app')->label('Мобильное приложение'),
                Toggle::make('has_api')->label('API доступ'),
                TextInput::make('tech_stack')->label('Технологический стек'),
            ]),
            Section::make('Продавцы и товары')->columns(2)->schema([
                TextInput::make('total_sellers')->label('Продавцов')->numeric(),
                TextInput::make('active_listings')->label('Активных объявлений')->numeric(),
                TextInput::make('product_categories')->label('Категорий')->numeric(),
                TextInput::make('avg_products_per_seller')->label('Товаров на продавца')->numeric(),
            ]),
            Section::make('Покупатели и спрос')->columns(2)->schema([
                TextInput::make('total_buyers')->label('Покупателей')->numeric(),
                TextInput::make('monthly_active_users')->label('Активных пользователей/мес')->numeric(),
                TextInput::make('avg_monthly_orders')->label('Заказов/месяц')->numeric(),
                TextInput::make('annual_gmv')->label('Годовой GMV')->numeric(),
            ]),
            Section::make('Логистика и доставка')->columns(2)->schema([
                Toggle::make('operates_fulfillment_centers')->label('Свои склады'),
                TextInput::make('avg_delivery_time_days')->label('Средн. доставка (дни)')->numeric(),
                TextInput::make('coverage_cities')->label('Городов покрытия')->numeric(),
                Toggle::make('offers_same_day_delivery')->label('Доставка в день'),
            ]),
            Section::make('Комиссии и платежи')->columns(2)->schema([
                TextInput::make('seller_commission_percent')->label('Комиссия продавца %')->numeric(),
                TextInput::make('buyer_fee_percent')->label('Комиссия покупателя %')->numeric(),
                TagsInput::make('payment_methods')->label('Способы оплаты'),
                Toggle::make('offers_seller_financing')->label('Финансирование продавца'),
            ]),
            Section::make('Рейтинг и метрики')->columns(2)->schema([
                TextInput::make('rating')->label('Рейтинг')->numeric()->disabled(),
                TextColumn::make('reviews_count')->label('Отзывов')->numeric()->disabled(),
                TextInput::make('seller_satisfaction_percent')->label('Удовл. продавцов %')->numeric(),
                TextInput::make('buyer_satisfaction_percent')->label('Удовл. покупателей %')->numeric(),
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
            TextColumn::make('marketplace_name')->label('Маркетплейс')->searchable()->sortable(),
            BadgeColumn::make('marketplace_type')->label('Тип'),
            TextColumn::make('total_sellers')->label('Продавцов')->numeric(),
            TextColumn::make('monthly_active_users')->label('Активных/мес')->numeric(),
            TextColumn::make('avg_monthly_orders')->label('Заказов/мес'),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            ToggleColumn::make('is_active')->label('Активен'),
        ])->defaultSort('marketplace_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->db->transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            $this->logger->info('Marketplace action',['user'=>$this->guard->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
