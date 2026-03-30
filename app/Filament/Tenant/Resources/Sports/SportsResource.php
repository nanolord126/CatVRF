<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Sports;

use App\Models\Sports;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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

final class SportsResource extends Resource
{
    protected static ?string $model = Sports::class;
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('organization_code')->label('Код организации')->required()->hidden(),
                TextInput::make('organization_name')->label('Название организации')->required(),
                Select::make('organization_type')->label('Тип организации')->options([
                    'gym' => 'Тренажерный зал','studio' => 'Студия','club' => 'Спортивный клуб','academy' => 'Академия',
                    'coach' => 'Личный тренер','facility' => 'Сооружение','team' => 'Команда',
                ])->required(),
                Select::make('main_sport')->label('Основной вид спорта')->options([
                    'boxing' => 'Бокс','fitness' => 'Фитнес','swimming' => 'Плавание','football' => 'Футбол',
                    'tennis' => 'Теннис','basketball' => 'Баскетбол','volleyball' => 'Волейбол','yoga' => 'Йога',
                ])->required(),
                TextInput::make('phone')->label('Телефон')->tel()->required(),
                FileUpload::make('logo')->label('Логотип')->image()->directory('sports'),
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
            Section::make('Инфраструктура')->columns(2)->schema([
                TextInput::make('total_area_sqm')->label('Площадь (м²)')->numeric(),
                TextInput::make('courts_fields_count')->label('Спортзалов/площадок')->numeric(),
                TextInput::make('equipment_count')->label('Единиц оборудования')->numeric(),
                Toggle::make('has_shower_rooms')->label('Душевые'),
                Toggle::make('has_lockers')->label('Шкафчики'),
                Toggle::make('has_cafe')->label('Кафе'),
                Toggle::make('has_parking')->label('Парковка'),
                Toggle::make('has_wifi')->label('WiFi'),
            ]),
            Section::make('Программы и услуги')->schema([
                Repeater::make('programs')->label('Программы')->schema([
                    TextInput::make('program_name')->label('Название программы')->required(),
                    TextInput::make('duration_weeks')->label('Длительность (недели)')->numeric(),
                    TextInput::make('participants_limit')->label('Макс участников')->numeric(),
                    TextInput::make('price_per_week')->label('Цена в неделю')->numeric(),
                ])->columnSpanFull(),
            ]),
            Section::make('Команда и тренеры')->columns(2)->schema([
                TextInput::make('total_coaches')->label('Тренеров')->numeric(),
                TextInput::make('certified_coaches')->label('Сертифицированных')->numeric(),
                TextInput::make('support_staff')->label('Вспомогательный персонал')->numeric(),
                Toggle::make('has_nutritionist')->label('Диетолог'),
                Toggle::make('has_physiotherapist')->label('Физиотерапевт'),
            ]),
            Section::make('Членство и цены')->columns(2)->schema([
                TextInput::make('monthly_membership')->label('Членство в месяц')->numeric(),
                TextInput::make('daily_pass_price')->label('Дневной проход')->numeric(),
                Toggle::make('offers_personal_training')->label('Персональный тренинг'),
                TextInput::make('personal_training_price')->label('Цена личного тренинга')->numeric(),
            ]),
            Section::make('Рейтинг и достижения')->columns(2)->schema([
                TextInput::make('rating')->label('Рейтинг')->numeric()->disabled(),
                TextInput::make('members_count')->label('Членов')->numeric(),
                TextInput::make('achievements')->label('Достижения')->maxLength(500),
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
            TextColumn::make('organization_name')->label('Организация')->searchable()->sortable(),
            BadgeColumn::make('organization_type')->label('Тип'),
            BadgeColumn::make('main_sport')->label('Спорт'),
            TextColumn::make('city')->label('Город'),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            TextColumn::make('members_count')->label('Членов'),
            ToggleColumn::make('is_active')->label('Активна'),
        ])->defaultSort('organization_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        DB::transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            Log::channel('audit')->info('Sports organization action',['user'=>auth()->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
