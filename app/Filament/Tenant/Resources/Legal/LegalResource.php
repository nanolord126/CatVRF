<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Legal;

use App\Models\Legal;
use Filament\Forms\Components\{DatePicker,FileUpload,Grid,Repeater,RichEditor,Section,Select,TagsInput,TextInput,Toggle};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{BadgeColumn,ImageColumn,TextColumn,ToggleColumn};
use Filament\Tables\Table;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class LegalResource extends Resource
{
    protected static ?string $model = Legal::class;
    protected static ?string $navigationIcon = 'heroicon-o-scale-3d';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 17;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('firm_code')->label('Код фирмы')->required()->hidden(),
                TextInput::make('firm_name')->label('Название фирмы')->required(),
                Select::make('firm_type')->label('Тип фирмы')->options([
                    'law_firm' => 'Юридическая фирма','solo_practice' => 'Частная практика','corporate' => 'Корпоративное',
                    'non_profit' => 'НКО','government' => 'Государственное','educational' => 'Образовательное',
                ])->required(),
                TextInput::make('bar_license')->label('Лицензия коллегии адвокатов')->required(),
                TextInput::make('year_founded')->label('Год основания')->numeric(),
                FileUpload::make('logo')->label('Логотип')->image()->directory('legal'),
            ]),
            Section::make('Адрес')->columns(2)->schema([
                TextInput::make('address')->label('Адрес')->required()->columnSpanFull(),
                TextInput::make('city')->label('Город')->required(),
                TextInput::make('phone')->label('Телефон')->tel()->required(),
                TextInput::make('email')->label('Email')->email()->required(),
            ]),
            Section::make('Описание и специализация')->schema([
                RichEditor::make('description')->label('Описание')->columnSpanFull(),
                TagsInput::make('practice_areas')->label('Области практики')->required(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Section::make('Команда')->columns(2)->schema([
                TextInput::make('total_lawyers')->label('Адвокатов')->numeric()->required(),
                TextInput::make('senior_lawyers')->label('Старших адвокатов')->numeric(),
                TextInput::make('junior_lawyers')->label('Младших адвокатов')->numeric(),
                TextInput::make('paralegals')->label('Помощников')->numeric(),
                TextInput::make('staff_count')->label('Всего сотрудников')->numeric(),
            ]),
            Section::make('Экспертиза и опыт')->schema([
                Repeater::make('specializations')->label('Специализации')->schema([
                    TextInput::make('specialization_name')->label('Специализация')->required(),
                    TextInput::make('years_experience')->label('Лет опыта')->numeric(),
                    TextInput::make('successful_cases')->label('Успешных дел')->numeric(),
                ])->columnSpanFull(),
            ]),
            Section::make('Рейтинг и сертификации')->columns(2)->schema([
                TextInput::make('rating')->label('Рейтинг')->numeric()->disabled(),
                TextInput::make('reviews_count')->label('Отзывов')->numeric()->disabled(),
                TextInput::make('win_rate_percent')->label('Процент побед %')->numeric(),
                TagsInput::make('certifications')->label('Сертификации'),
                Toggle::make('is_verified')->label('Верифицирована'),
            ]),
            Section::make('Услуги и тарифы')->columns(2)->schema([
                Toggle::make('offers_free_consultation')->label('Бесплатная консультация'),
                TextInput::make('hourly_rate')->label('Почасовая ставка')->numeric(),
                Toggle::make('offers_payment_plans')->label('Планы платежей'),
                TextInput::make('min_case_fee')->label('Минимальный гонорар')->numeric(),
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
            TextColumn::make('firm_name')->label('Фирма')->searchable()->sortable(),
            BadgeColumn::make('firm_type')->label('Тип'),
            TextColumn::make('city')->label('Город'),
            TextColumn::make('total_lawyers')->label('Адвокатов')->numeric(),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            TextColumn::make('win_rate_percent')->label('Побед %'),
            ToggleColumn::make('is_active')->label('Активна'),
        ])->defaultSort('firm_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        DB::transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            Log::channel('audit')->info('Legal firm action',['user'=>auth()->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
