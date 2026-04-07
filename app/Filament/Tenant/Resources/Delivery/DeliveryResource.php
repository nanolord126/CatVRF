<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Delivery;




use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Models\Delivery;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class DeliveryResource extends Resource
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    protected static ?string $model = Delivery::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 19;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('company_code')->label('Код компании')->required()->hidden(),
                TextInput::make('company_name')->label('Название компании')->required(),
                TextInput::make('delivery_type')->label('Тип доставки')->required(),
                TextInput::make('phone')->label('Телефон')->tel()->required(),
                TextInput::make('email')->label('Email')->email()->required(),
                FileUpload::make('logo')->label('Логотип')->image()->directory('delivery'),
            ]),
            Section::make('Адрес и покрытие')->columns(2)->schema([
                TextInput::make('headquarters_address')->label('Адрес офиса')->required()->columnSpanFull(),
                TextInput::make('main_city')->label('Главный город')->required(),
                TextInput::make('coverage_cities')->label('Города доставки')->required(),
                TextInput::make('coverage_radius_km')->label('Радиус покрытия (км)')->numeric(),
            ]),
            Section::make('Описание')->schema([
                RichEditor::make('description')->label('Описание')->columnSpanFull(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Section::make('Парк доставки')->columns(2)->schema([
                TextInput::make('total_vehicles')->label('Всего машин')->numeric()->required(),
                TextInput::make('motorcycles')->label('Мотоциклов')->numeric(),
                TextInput::make('cars')->label('Машин')->numeric(),
                TextInput::make('vans')->label('Фургонов')->numeric(),
                TextInput::make('trucks')->label('Грузовиков')->numeric(),
            ]),
            Section::make('Курьеры и персонал')->columns(2)->schema([
                TextInput::make('total_couriers')->label('Курьеров')->numeric()->required(),
                TextInput::make('full_time_couriers')->label('Полный рабочий день')->numeric(),
                TextInput::make('part_time_couriers')->label('Неполный день')->numeric(),
                TextInput::make('staff_count')->label('Персонала в офисе')->numeric(),
            ]),
            Section::make('Услуги доставки')->columns(2)->schema([
                TagsInput::make('delivery_types')->label('Типы доставки')->required(),
                Toggle::make('offers_same_day')->label('Доставка в день заказа'),
                Toggle::make('offers_scheduled')->label('Плановая доставка'),
                Toggle::make('offers_fragile')->label('Хрупкие предметы'),
                Toggle::make('offers_insured')->label('Страховка груза'),
                Toggle::make('offers_temperature_controlled')->label('Термоконтроль'),
                Toggle::make('offers_24_7')->label('24/7 доставка'),
            ]),
            Section::make('Тарифы и цены')->columns(2)->schema([
                TextInput::make('base_delivery_price')->label('Базовая цена')->numeric(),
                TextInput::make('price_per_km')->label('Цена за км')->numeric(),
                TextInput::make('price_per_kg')->label('Цена за кг')->numeric(),
                TextInput::make('min_delivery_sum')->label('Минимум')->numeric(),
            ]),
            Section::make('Рейтинг и метрики')->columns(2)->schema([
                TextInput::make('rating')->label('Рейтинг')->numeric()->disabled(),
                TextInput::make('on_time_percent')->label('Вовремя %')->numeric(),
                TextInput::make('total_deliveries')->label('Всего доставок')->numeric(),
                TextInput::make('avg_delivery_time_min')->label('Средн. время (мин)')->numeric(),
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
            TextColumn::make('company_name')->label('Компания')->searchable()->sortable(),
            TextColumn::make('main_city')->label('Город'),
            TextColumn::make('total_vehicles')->label('Машин')->numeric(),
            TextColumn::make('total_couriers')->label('Курьеров')->numeric(),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            TextColumn::make('on_time_percent')->label('Вовремя %'),
            ToggleColumn::make('is_active')->label('Активна'),
        ])->defaultSort('company_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->db->transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            $this->logger->info('Delivery company action',['user'=>$this->guard->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
