<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hospitality;

use App\Models\Hospitality;
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

final class HospitalityResource extends Resource
{
    protected static ?string $model = Hospitality::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('venue_code')->label('Код заведения')->required()->hidden(),
                TextInput::make('venue_name')->label('Название')->required(),
                Select::make('establishment_type')->label('Тип заведения')->options([
                    'restaurant' => 'Ресторан','bar' => 'Бар','cafe' => 'Кафе','bistro' => 'Бистро',
                    'pub' => 'Паб','club' => 'Ночной клуб','lounge' => 'Лаунж','brewery' => 'Пивоварня',
                ])->required(),
                TextInput::make('phone')->label('Телефон')->tel()->required(),
                TextInput::make('email')->label('Email')->email()->required(),
                FileUpload::make('main_photo')->label('Фото')->image()->directory('hospitality'),
            ]),
            Section::make('Адрес')->columns(2)->schema([
                TextInput::make('address')->label('Адрес')->required()->columnSpanFull(),
                TextInput::make('city')->label('Город')->required(),
                TextInput::make('latitude')->label('Широта')->numeric(),
                TextInput::make('longitude')->label('Долгота')->numeric(),
            ]),
            Section::make('Описание')->schema([
                RichEditor::make('description')->label('Описание')->columnSpanFull(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Section::make('Атмосфера и интерьер')->columns(2)->schema([
                TagsInput::make('ambiance_tags')->label('Атмосфера')->required(),
                TextInput::make('cuisine_types')->label('Кухня')->required(),
                TextInput::make('price_range')->label('Ценовой диапазон'),
                Toggle::make('live_music')->label('Живая музыка'),
                Toggle::make('has_terrace')->label('Терраса'),
                Toggle::make('has_private_rooms')->label('Закрытые комнаты'),
            ]),
            Section::make('Вместимость и сервис')->columns(2)->schema([
                TextInput::make('total_capacity')->label('Общая вместимость')->numeric()->required(),
                TextInput::make('seating_capacity')->label('Мест')->numeric(),
                TextInput::make('bar_seats')->label('Мест у бара')->numeric(),
                Toggle::make('accepts_reservations')->label('Принимает броми'),
                Toggle::make('requires_reservation')->label('Требует брони'),
                Toggle::make('offers_delivery')->label('Доставка'),
            ]),
            Section::make('Меню и напитки')->columns(2)->schema([
                TextInput::make('menu_items_count')->label('Позиций в меню')->numeric(),
                TextInput::make('wine_selection_count')->label('Вин')->numeric(),
                TextInput::make('beer_selection_count')->label('Сортов пива')->numeric(),
                Toggle::make('has_cocktail_menu')->label('Коктейли'),
                Toggle::make('vegan_options')->label('Веган опции'),
                Toggle::make('gluten_free_options')->label('Без глютена'),
            ]),
            Section::make('Персонал и часы')->columns(2)->schema([
                TextInput::make('staff_count')->label('Персонала')->numeric(),
                TextInput::make('chefs_count')->label('Поваров')->numeric(),
                TextInput::make('waiters_count')->label('Официантов')->numeric(),
                TextInput::make('opening_time')->label('Открытие'),
                TextInput::make('closing_time')->label('Закрытие'),
                Toggle::make('open_24_7')->label('Открыто 24/7'),
            ]),
            Section::make('Рейтинг и отзывы')->columns(2)->schema([
                TextInput::make('rating')->label('Рейтинг')->numeric()->disabled(),
                TextInput::make('reviews_count')->label('Отзывов')->numeric()->disabled(),
                TextInput::make('avg_bill')->label('Средний чек')->numeric(),
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
            ImageColumn::make('main_photo')->label('Фото')->square()->size(40),
            TextColumn::make('venue_name')->label('Заведение')->searchable()->sortable(),
            BadgeColumn::make('establishment_type')->label('Тип'),
            TextColumn::make('city')->label('Город'),
            TextColumn::make('total_capacity')->label('Вместимость')->numeric(),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            TextColumn::make('avg_bill')->label('Средний чек'),
            ToggleColumn::make('is_active')->label('Активно'),
        ])->defaultSort('venue_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        DB::transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            Log::channel('audit')->info('Hospitality venue action',['user'=>auth()->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
