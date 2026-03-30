<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Services;

use App\Models\Services;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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

final class ServicesResource extends Resource
{
    protected static ?string $model = Services::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 24;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('provider_code')->label('Код провайдера')->required()->hidden(),
                TextInput::make('provider_name')->label('Название услуги')->required(),
                Select::make('service_category')->label('Категория услуги')->options([
                    'cleaning' => 'Уборка','plumbing' => 'Сантехника','electrical' => 'Электричество',
                    'hvac' => 'HVAC','painting' => 'Покраска','landscaping' => 'Благоустройство',
                    'pest_control' => 'Дезинсекция','moving' => 'Переезд','repair' => 'Ремонт',
                ])->required(),
                TextInput::make('phone')->label('Телефон')->tel()->required(),
                TextInput::make('email')->label('Email')->email()->required(),
                FileUpload::make('profile_photo')->label('Фото')->image()->directory('services'),
            ]),
            Section::make('Адрес и область обслуживания')->columns(2)->schema([
                TextInput::make('base_address')->label('Адрес офиса')->required()->columnSpanFull(),
                TextInput::make('main_city')->label('Основной город')->required(),
                TextInput::make('service_radius_km')->label('Радиус обслуживания (км)')->numeric(),
                TagsInput::make('service_areas')->label('Служебные площади'),
            ]),
            Section::make('Описание')->schema([
                RichEditor::make('description')->label('Описание')->columnSpanFull(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Section::make('Услуги')->schema([
                Repeater::make('services')->label('Предлагаемые услуги')->schema([
                    TextInput::make('service_name')->label('Название услуги')->required(),
                    TextInput::make('service_price')->label('Цена')->numeric(),
                    TextInput::make('duration_hours')->label('Длительность (часы)')->numeric(),
                ])->columnSpanFull(),
            ]),
            Section::make('Сертификации и лицензии')->columns(2)->schema([
                TextInput::make('business_license_number')->label('Номер лицензии')->required(),
                TagsInput::make('certifications')->label('Сертификации'),
                Toggle::make('insured_and_bonded')->label('Застрахован и под залогом'),
                Toggle::make('background_checked')->label('Проверка биографии'),
            ]),
            Section::make('Команда и опыт')->columns(2)->schema([
                TextInput::make('total_team_members')->label('Членов команды')->numeric(),
                TextInput::make('years_in_business')->label('Лет в бизнесе')->numeric(),
                TextInput::make('completed_projects')->label('Завершено проектов')->numeric(),
                TextInput::make('satisfaction_rate_percent')->label('Удовлетворение %')->numeric(),
            ]),
            Section::make('Доступность и гибкость')->columns(2)->schema([
                TextInput::make('opening_time')->label('Открытие'),
                TextInput::make('closing_time')->label('Закрытие'),
                Toggle::make('available_weekends')->label('Выходные'),
                Toggle::make('available_24_7')->label('24/7 доступность'),
                Toggle::make('emergency_service')->label('Срочная служба'),
            ]),
            Section::make('Рейтинг и отзывы')->columns(2)->schema([
                TextInput::make('rating')->label('Рейтинг')->numeric()->disabled(),
                TextInput::make('reviews_count')->label('Отзывов')->numeric()->disabled(),
                TextInput::make('repeat_customer_percent')->label('Повторные клиенты %')->numeric(),
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
            ImageColumn::make('profile_photo')->label('Фото')->square()->size(40),
            TextColumn::make('provider_name')->label('Провайдер')->searchable()->sortable(),
            BadgeColumn::make('service_category')->label('Категория'),
            TextColumn::make('main_city')->label('Город'),
            TextColumn::make('total_team_members')->label('Команда')->numeric(),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            TextColumn::make('completed_projects')->label('Проектов'),
            ToggleColumn::make('is_active')->label('Активен'),
        ])->defaultSort('provider_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        DB::transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            Log::channel('audit')->info('Services provider action',['user'=>auth()->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
