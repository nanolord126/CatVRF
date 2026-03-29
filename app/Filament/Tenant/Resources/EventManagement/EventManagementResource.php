<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\EventManagement;

use App\Models\EventManagement;
use Filament\Forms\Components\{DatePicker,FileUpload,Grid,Repeater,RichEditor,Section,Select,TagsInput,TextInput,Toggle};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{BadgeColumn,ImageColumn,TextColumn,ToggleColumn};
use Filament\Tables\Table;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class EventManagementResource extends Resource
{
    protected static ?string $model = EventManagement::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('event_code')->label('Код события')->required()->hidden(),
                TextInput::make('event_name')->label('Название события')->required(),
                Select::make('event_type')->label('Тип события')->options([
                    'conference' => 'Конференция','seminar' => 'Семинар','workshop' => 'Воркшоп',
                    'festival' => 'Фестиваль','corporate' => 'Корпоративное','wedding' => 'Свадьба',
                    'party' => 'Вечеринка','concert' => 'Концерт','exhibition' => 'Выставка',
                ])->required(),
                Select::make('status')->label('Статус')->options([
                    'planning' => 'Планирование','scheduled' => 'Назначено','in_progress' => 'В процессе','completed' => 'Завершено',
                ])->required(),
                DatePicker::make('event_date')->label('Дата события')->required(),
                TextInput::make('duration_hours')->label('Длительность (часы)')->numeric(),
            ]),
            Section::make('Описание')->schema([
                RichEditor::make('description')->label('Описание')->required()->columnSpanFull(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(255)->columnSpanFull(),
                TextInput::make('theme')->label('Тема')->maxLength(100),
            ])->columns(2),
            Section::make('Место проведения')->columns(2)->schema([
                TextInput::make('venue_name')->label('Название площадки')->required(),
                TextInput::make('venue_address')->label('Адрес')->required()->columnSpanFull(),
                TextInput::make('venue_city')->label('Город')->required(),
                TextInput::make('capacity')->label('Вместимость')->numeric(),
                TextInput::make('latitude')->label('Широта')->numeric(),
                TextInput::make('longitude')->label('Долгота')->numeric(),
            ]),
            Section::make('Бюджет и финансы')->columns(2)->schema([
                TextInput::make('total_budget')->label('Бюджет')->numeric()->required(),
                TextInput::make('spent_budget')->label('Потрачено')->numeric(),
                TextInput::make('expected_attendees')->label('Ожидаемые гости')->numeric(),
                TextInput::make('ticket_price')->label('Цена билета')->numeric(),
                TextInput::make('revenue')->label('Доход')->numeric(),
            ]),
            Section::make('Организация')->columns(2)->schema([
                TextInput::make('organizer_name')->label('Организатор')->required(),
                TextInput::make('organizer_phone')->label('Телефон')->tel(),
                TextInput::make('organizer_email')->label('Email')->email(),
                TextInput::make('staff_count')->label('Персонал')->numeric(),
                TextInput::make('volunteers_count')->label('Волонтеры')->numeric(),
            ]),
            Section::make('Программа')->schema([
                Repeater::make('schedule')->label('Расписание')->schema([
                    TextInput::make('time')->label('Время'),TextInput::make('activity')->label('Мероприятие'),
                ])->columnSpanFull(),
            ]),
            Section::make('Рейтинг')->columns(2)->schema([
                TextInput::make('rating')->label('Рейтинг')->numeric()->disabled(),
                TextInput::make('reviews_count')->label('Отзывов')->numeric()->disabled(),
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
            TextColumn::make('event_name')->label('Название')->searchable()->sortable(),
            BadgeColumn::make('event_type')->label('Тип'),
            BadgeColumn::make('status')->label('Статус'),
            TextColumn::make('event_date')->label('Дата'),
            TextColumn::make('capacity')->label('Вместимость')->numeric(),
            TextColumn::make('total_budget')->label('Бюджет')->numeric(),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            ToggleColumn::make('is_active')->label('Активно'),
        ])->defaultSort('event_date');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        DB::transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            Log::channel('audit')->info('EventManagement action',['user'=>auth()->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
