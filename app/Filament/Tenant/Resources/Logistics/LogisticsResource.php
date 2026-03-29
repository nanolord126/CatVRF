<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Logistics;
use App\Domains\Logistics\Models\Courier;
use Filament\Forms\Components\{DatePicker,FileUpload,Hidden,RichEditor,Section,Select,TagsInput,Textarea,TextInput,Toggle,Repeater};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{BadgeColumn,TextColumn,ImageColumn};
use Filament\Tables\Actions\{BulkActionGroup,DeleteBulkAction,EditAction,ViewAction};
use Filament\Tables\Filters\{Filter,SelectFilter};
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class LogisticsResource extends Resource
{
    protected static ?string $model = Courier::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Вертикали';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('tenant_id')->default(fn()=>filament()->getTenant()->id),
            Hidden::make('correlation_id')->default(fn()=>Str::uuid()->toString()),
            Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    TextInput::make('courier_code')->label('Код')->unique(ignoreRecord:true)->columnSpan(1),
                    TextInput::make('full_name')->label('ФИО')->required()->columnSpan(1),
                    Select::make('status')->label('Статус')->options(['active'=>'Активный','inactive'=>'Неактивный','on_break'=>'На перерыве','suspended'=>'Приостановлен'])->columnSpan(1),
                    TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                ]),
            Section::make('Адрес')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('city')->label('Город')->required()->columnSpan(1),
                    TextInput::make('district')->label('Район')->columnSpan(1),
                    TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                    TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                ]),
            Section::make('Транспортные средства')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('vehicle_type')->label('Тип')->columnSpan(1),
                    TextInput::make('vehicle_brand')->label('Марка')->columnSpan(1),
                    TextInput::make('license_plate')->label('Номер')->columnSpan(1),
                    TextInput::make('max_weight_kg')->label('Макс вес (кг)')->numeric()->columnSpan(1),
                    TextInput::make('max_volume_cbm')->label('Макс объём (м³)')->numeric()->columnSpan(1),
                ]),
            Section::make('Показатели и рейтинг')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('rating')->label('Рейтинг')->numeric(decimals:1)->max(5)->columnSpan(1),
                    TextInput::make('total_deliveries')->label('Доставок')->numeric()->columnSpan(1),
                    TextInput::make('on_time_delivery_percent')->label('Вовремя (%)')->numeric()->columnSpan(1),
                    TextInput::make('repeat_customer_rate')->label('Постоянные (%)')->numeric()->columnSpan(1),
                ]),
            Section::make('Услуги')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Toggle::make('accepts_cash')->label('Наличные')->columnSpan(1),
                    Toggle::make('accepts_card')->label('Карта')->columnSpan(1),
                    Toggle::make('accepts_crypto')->label('Крипто')->columnSpan(1),
                    Toggle::make('offers_insurance')->label('Страховка')->columnSpan(1),
                    Toggle::make('offers_tracking')->label('Трекинг')->columnSpan(1),
                    Toggle::make('available_24_7')->label('24/7')->columnSpan(1),
                ]),
            Section::make('Квалификация')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Toggle::make('background_check_passed')->label('Проверка')->columnSpan(1),
                    Toggle::make('license_valid')->label('Лицензия')->columnSpan(1),
                    Toggle::make('insurance_valid')->label('Страховка')->columnSpan(1),
                    DatePicker::make('background_check_date')->label('Дата проверки')->columnSpan(1),
                ]),
            Section::make('Цены')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('base_delivery_price')->label('Базовая доставка (₽)')->numeric()->columnSpan(1),
                    TextInput::make('per_km_price')->label('За км (₽)')->numeric()->columnSpan(1),
                    TextInput::make('per_kg_price')->label('За кг (₽)')->numeric()->columnSpan(1),
                ]),
            Section::make('Час работы')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('working_hours_start')->label('С')->columnSpan(1),
                    TextInput::make('working_hours_end')->label('По')->columnSpan(1),
                    Toggle::make('works_weekends')->label('Выходные')->columnSpan(1),
                    Toggle::make('works_nights')->label('Ночи')->columnSpan(1),
                ]),
            Section::make('Медиа')
                ->collapsed()
                ->schema([
                    FileUpload::make('profile_photo')->label('Фото')->image()->directory('logistics-profiles'),
                    FileUpload::make('vehicle_photo')->label('Фото авто')->image()->directory('logistics-vehicles'),
                ]),
            Section::make('SEO')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('meta_title')->label('Meta Title')->maxLength(60),
                    Textarea::make('meta_description')->label('Meta Description')->maxLength(160)->rows(2)->columnSpan(2),
                ]),
            Section::make('Управление')
                ->collapsed()
                ->columns(3)
                ->schema([
                    Toggle::make('is_active')->label('Активно')->default(true),
                    Toggle::make('is_featured')->label('Избранное')->default(false),
                    Toggle::make('verified')->label('Проверено')->default(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('profile_photo')->label('Фото')->size(40),
            TextColumn::make('full_name')->label('ФИО')->searchable()->sortable()->weight('bold')->limit(30),
            TextColumn::make('city')->label('Город')->searchable(),
            TextColumn::make('rating')->label('Рейтинг')->numeric(decimals:1)->badge()->color('warning')->sortable(),
            TextColumn::make('total_deliveries')->label('Доставок')->numeric(),
            TextColumn::make('on_time_delivery_percent')->label('Вовремя (%)')->numeric()->badge()->color('success'),
            BadgeColumn::make('available_24_7')->label('24/7')->colors(['warning'=>true,'gray'=>false]),
            BadgeColumn::make('offers_tracking')->label('Трекинг')->colors(['info'=>true,'gray'=>false]),
            BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning'=>true,'gray'=>false]),
            TextColumn::make('phone')->label('Телефон')->toggleable(isToggledHiddenByDefault:true),
        ])->filters([
            Filter::make('available')->query(fn(Builder $q)=>$q->where('is_active',true))->label('Доступны'),
            Filter::make('tracking')->query(fn(Builder $q)=>$q->where('offers_tracking',true))->label('С трекингом'),
        ])->actions([ViewAction::make(),EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('rating','desc');
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListLogistics::route('/'),'create'=>Pages\CreateLogistics::route('/create'),'edit'=>Pages\EditLogistics::route('/{record}/edit'),];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id',filament()->getTenant()->id);
    }
}
