<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment;

use App\Models\Entertainment;
use Filament\Forms\Components\{DatePicker,FileUpload,Grid,Repeater,RichEditor,Section,Select,TagsInput,TextInput,Toggle};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{BadgeColumn,ImageColumn,TextColumn,ToggleColumn};
use Filament\Tables\Table;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class EntertainmentResource extends Resource
{
    protected static ?string $model = Entertainment::class;
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('company_code')->label('Код')->required()->maxLength(50)->hidden(),
                TextInput::make('company_name')->label('Название')->required()->maxLength(255),
                Select::make('type')->label('Тип')->options([
                    'cinema' => 'Кинотеатр','theater' => 'Театр','concert' => 'Концерт-холл','club' => 'Ночной клуб',
                    'festival' => 'Фестиваль','exhibition' => 'Выставка','theme_park' => 'Парк развлечений',
                ])->required(),
                TextInput::make('phone')->label('Телефон')->tel()->required(),
                TextInput::make('email')->label('Email')->email()->required(),
                TextInput::make('website')->label('Сайт')->url(),
                FileUpload::make('logo')->label('Логотип')->image()->directory('entertainment'),
            ]),
            Section::make('Адрес')->columns(2)->schema([
                TextInput::make('address')->label('Адрес')->required()->columnSpanFull(),
                TextInput::make('city')->label('Город')->required(),
                TextInput::make('latitude')->label('Широта')->numeric(),
                TextInput::make('longitude')->label('Долгота')->numeric(),
            ]),
            Section::make('Описание')->schema([
                RichEditor::make('description')->label('Описание')->required()->columnSpanFull(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(500)->columnSpanFull(),
            ]),
            Section::make('Вместимость')->columns(2)->schema([
                TextInput::make('total_capacity')->label('Общая вместимость')->numeric()->required(),
                TextInput::make('halls_count')->label('Залов/сцен')->numeric(),
                TextInput::make('parking_spaces')->label('Парковочных мест')->numeric(),
                Toggle::make('wheelchair_accessible')->label('Доступна для инвалидов'),
            ]),
            Section::make('События')->schema([
                Repeater::make('events')->label('События на неделю')->schema([
                    TextInput::make('event_name')->label('Название события')->required(),
                    TextInput::make('event_date')->label('Дата')->required(),
                    TextInput::make('start_time')->label('Начало')->required(),
                    TextInput::make('tickets_available')->label('Билетов доступно')->numeric(),
                ])->columnSpanFull(),
            ]),
            Section::make('Программирование')->schema([
                TagsInput::make('main_genres')->label('Основные жанры')->required(),
                TextInput::make('events_per_month')->label('События в месяц')->numeric(),
                TextInput::make('annual_events')->label('Событий в год')->numeric(),
                TextInput::make('vip_events_percent')->label('VIP события %')->numeric(),
            ])->columns(2),
            Section::make('Рейтинг и отзывы')->columns(2)->schema([
                TextInput::make('rating')->label('Рейтинг')->numeric()->disabled(),
                TextInput::make('review_count')->label('Отзывов')->numeric()->disabled(),
                TextInput::make('average_rating')->label('Средний рейтинг')->numeric()->disabled(),
            ]),
            Section::make('Скрытые поля')->hidden()->schema([
                TextInput::make('tenant_id')->default(fn()=>filament()->getTenant()->id)->hidden(),
                TextInput::make('correlation_id')->default(fn()=>Str::uuid()->toString())->hidden(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('logo')->label('Логотип')->square()->size(40),
            TextColumn::make('company_name')->label('Название')->searchable()->sortable(),
            BadgeColumn::make('type')->label('Тип'),
            TextColumn::make('city')->label('Город')->sortable(),
            TextColumn::make('total_capacity')->label('Вместимость')->numeric(),
            TextColumn::make('events_per_month')->label('События/мес'),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge()->color('warning'),
            ToggleColumn::make('is_active')->label('Активен'),
            ToggleColumn::make('is_featured')->label('Рекомендуемый'),
        ])->defaultSort('company_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = Str::uuid()->toString();
        Log::channel('audit')->info('Entertainment create/update',['user'=>auth()->id(),'correlation_id'=>$data['correlation_id']]);
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
