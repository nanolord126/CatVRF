<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Construction;

use App\Models\Construction;
use Filament\Forms\Components\{DatePicker,FileUpload,Grid,Repeater,RichEditor,Section,Select,TagsInput,TextInput,Toggle};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{BadgeColumn,ImageColumn,TextColumn,ToggleColumn};
use Filament\Tables\Table;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class ConstructionResource extends Resource
{
    protected static ?string $model = Construction::class;
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 18;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('project_code')->label('Код проекта')->required()->hidden(),
                TextInput::make('project_name')->label('Название проекта')->required(),
                Select::make('project_type')->label('Тип проекта')->options([
                    'residential' => 'Жилой','commercial' => 'Коммерческий','industrial' => 'Промышленный',
                    'infrastructure' => 'Инфраструктура','renovation' => 'Реконструкция','repair' => 'Ремонт',
                ])->required(),
                Select::make('status')->label('Статус')->options([
                    'planning' => 'Планирование','tender' => 'Тендер','in_progress' => 'В процессе','completed' => 'Завершено',
                ])->required(),
                DatePicker::make('start_date')->label('Дата начала')->required(),
                DatePicker::make('end_date')->label('Дата завершения'),
            ]),
            Section::make('Описание')->schema([
                RichEditor::make('description')->label('Описание')->required()->columnSpanFull(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Section::make('Размер и объем')->columns(2)->schema([
                TextInput::make('total_area_sqm')->label('Общая площадь (м²)')->numeric()->required(),
                TextInput::make('building_height_meters')->label('Высота здания (м)')->numeric(),
                TextInput::make('floors_count')->label('Этажей')->numeric(),
                TextInput::make('project_cost')->label('Стоимость проекта')->numeric()->required(),
                TextInput::make('budget_spent')->label('Потрачено бюджета')->numeric(),
            ]),
            Section::make('Организация')->columns(2)->schema([
                TextInput::make('contractor_name')->label('Подрядчик')->required(),
                TextInput::make('contractor_license')->label('Лицензия подрядчика')->required(),
                TextInput::make('architect_name')->label('Архитектор'),
                TextInput::make('project_manager')->label('Менеджер проекта'),
                TextInput::make('workers_count')->label('Рабочих')->numeric(),
            ]),
            Section::make('Материалы и ресурсы')->schema([
                Repeater::make('materials')->label('Материалы')->schema([
                    TextInput::make('material_name')->label('Материал')->required(),
                    TextInput::make('quantity')->label('Количество')->required(),
                    TextInput::make('unit')->label('Единица'),
                ])->columnSpanFull(),
            ]),
            Section::make('Специализации')->columns(2)->schema([
                TagsInput::make('work_types')->label('Виды работ')->required(),
                Toggle::make('has_safety_certifications')->label('Сертификаты безопасности'),
                Toggle::make('uses_eco_materials')->label('Эко-материалы'),
                Toggle::make('offers_warranty')->label('Гарантия'),
            ]),
            Section::make('Рейтинг и отзывы')->columns(2)->schema([
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
            TextColumn::make('project_name')->label('Проект')->searchable()->sortable(),
            BadgeColumn::make('project_type')->label('Тип'),
            BadgeColumn::make('status')->label('Статус'),
            TextColumn::make('total_area_sqm')->label('Площадь (м²)')->numeric(),
            TextColumn::make('project_cost')->label('Бюджет')->numeric(),
            TextColumn::make('budget_spent')->label('Потрачено'),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            ToggleColumn::make('is_active')->label('Активен'),
        ])->defaultSort('project_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        DB::transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            Log::channel('audit')->info('Construction project action',['user'=>auth()->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
