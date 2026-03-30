<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Enterprise;

use App\Models\Enterprise;
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

final class EnterpriseResource extends Resource
{
    protected static ?string $model = Enterprise::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 26;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('company_code')->label('Код компании')->required()->hidden(),
                TextInput::make('company_name')->label('Название компании')->required(),
                Select::make('company_type')->label('Тип компании')->options([
                    'startup' => 'Стартап','sme' => 'МСП','mid_market' => 'Средний бизнес','enterprise' => 'Предприятие',
                    'corporation' => 'Корпорация','non_profit' => 'НКО','cooperative' => 'Кооператив',
                ])->required(),
                TextInput::make('industry')->label('Индустрия')->required(),
                TextInput::make('phone')->label('Телефон')->tel()->required(),
                FileUpload::make('logo')->label('Логотип')->image()->directory('enterprise'),
            ]),
            Section::make('Адрес и контакты')->columns(2)->schema([
                TextInput::make('headquarters_address')->label('Адрес головного офиса')->required()->columnSpanFull(),
                TextInput::make('main_city')->label('Город')->required(),
                TextInput::make('email')->label('Email')->email()->required(),
                TextInput::make('website')->label('Веб-сайт')->url(),
            ]),
            Section::make('Описание')->schema([
                RichEditor::make('description')->label('Описание')->columnSpanFull(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Section::make('История и достижения')->columns(2)->schema([
                TextInput::make('year_founded')->label('Год основания')->numeric()->required(),
                TextInput::make('years_in_operation')->label('Лет в операции')->numeric(),
                TextInput::make('major_milestones')->label('Основные достижения')->columnSpanFull(),
                TagsInput::make('awards')->label('Награды'),
            ]),
            Section::make('Размер и масштаб')->columns(2)->schema([
                TextInput::make('total_employees')->label('Сотрудников')->numeric()->required(),
                TextInput::make('branches_count')->label('Филиалов')->numeric(),
                TextInput::make('annual_revenue')->label('Годовой доход')->numeric(),
                TextInput::make('market_capitalization')->label('Рыночная капитализация')->numeric(),
            ]),
            Section::make('Операционная информация')->schema([
                Repeater::make('business_units')->label('Бизнес-единицы')->schema([
                    TextInput::make('unit_name')->label('Название')->required(),
                    TextInput::make('unit_revenue')->label('Доход')->numeric(),
                    TextInput::make('employees')->label('Сотрудников')->numeric(),
                ])->columnSpanFull(),
            ]),
            Section::make('Управление и структура')->columns(2)->schema([
                TextInput::make('ceo_name')->label('CEO')->required(),
                TextInput::make('cto_name')->label('CTO'),
                TextInput::make('cfo_name')->label('CFO'),
                TextInput::make('board_members_count')->label('Членов совета')->numeric(),
            ]),
            Section::make('Финансовое состояние')->columns(2)->schema([
                TextInput::make('profit_margin_percent')->label('Маржа прибыли %')->numeric(),
                TextInput::make('debt_to_equity_ratio')->label('Отношение долга')->numeric(),
                Toggle::make('is_publicly_traded')->label('Публичная торговля'),
                TextInput::make('credit_rating')->label('Кредитный рейтинг'),
            ]),
            Section::make('Рейтинг и отзывы')->columns(2)->schema([
                TextInput::make('rating')->label('Рейтинг')->numeric()->disabled(),
                TextInput::make('reviews_count')->label('Отзывов')->numeric()->disabled(),
                TextInput::make('employee_satisfaction')->label('Удовл. сотрудников %')->numeric(),
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
            BadgeColumn::make('company_type')->label('Тип'),
            TextColumn::make('industry')->label('Индустрия'),
            TextColumn::make('total_employees')->label('Сотрудников')->numeric(),
            TextColumn::make('annual_revenue')->label('Доход')->numeric(),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            ToggleColumn::make('is_active')->label('Активна'),
        ])->defaultSort('company_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        DB::transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            Log::channel('audit')->info('Enterprise company action',['user'=>auth()->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
