<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Manufacturing;

    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables\Columns\{BadgeColumn,TextColumn,ImageColumn};
    use Filament\Tables\Actions\{BulkActionGroup,DeleteBulkAction,EditAction,ViewAction};
    use Filament\Tables\Filters\{Filter,SelectFilter};
    use Filament\Tables\Table;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;

    final class ManufacturingResource extends Resource
    {
        protected static ?string $model = Factory::class;
        protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn()=>filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn()=>Str::uuid()->toString()),
                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('factory_code')->label('Код')->unique(ignoreRecord:true)->columnSpan(1),
                        TextInput::make('factory_name')->label('Название')->required()->columnSpan(1),
                        Select::make('industry')->label('Отрасль')->options(['textile'=>'Текстиль','machinery'=>'Машины','electronics'=>'Электроника','food'=>'Пищевая','chemical'=>'Химическая'])->required()->columnSpan(1),
                        TextInput::make('phone')->label('Телефон')->tel()->columnSpan(1),
                    ]),
                Section::make('Адрес')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')->label('Адрес')->required()->columnSpan(2),
                        TextInput::make('city')->label('Город')->required()->columnSpan(1),
                        TextInput::make('district')->label('Район')->columnSpan(1),
                    ]),
                Section::make('О производстве')
                    ->collapsed()
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->columnSpan('full'),
                    ]),
                Section::make('Производственные мощности')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_area_sqm')->label('Площадь (м²)')->numeric()->columnSpan(1),
                        TextInput::make('production_lines')->label('Производственных линий')->numeric()->columnSpan(1),
                        TextInput::make('workers_count')->label('Рабочих')->numeric()->columnSpan(1),
                        TextInput::make('monthly_capacity')->label('Месячная мощность')->numeric()->columnSpan(1),
                        TextInput::make('daily_production_units')->label('Дневной выпуск (шт)')->numeric()->columnSpan(1),
                        Toggle::make('iso_9001_certified')->label('ISO 9001')->columnSpan(1),
                    ]),
                Section::make('Оборудование')
                    ->collapsed()
                    ->schema([
                        Repeater::make('equipment')->label('Оборудование')
                            ->schema([
                                TextInput::make('equipment_name')->label('Название')->required(),
                                TextInput::make('quantity')->label('Количество')->numeric(),
                                TextInput::make('year_of_manufacture')->label('Год выпуска')->numeric(),
                            ])->columnSpan('full'),
                    ]),
                Section::make('Продукция')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TagsInput::make('main_products')->label('Основная продукция')->columnSpan(2),
                        TextInput::make('product_types_count')->label('Видов продукции')->numeric()->columnSpan(1),
                        TextInput::make('annual_production_units')->label('Годовой выпуск (шт)')->numeric()->columnSpan(1),
                    ]),
                Section::make('Контроль качества')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_qa_department')->label('Отдел QA')->columnSpan(1),
                        Toggle::make('has_testing_lab')->label('Тестовая лаборатория')->columnSpan(1),
                        TextInput::make('defect_rate_percent')->label('Процент брака (%)')->numeric()->columnSpan(1),
                        TextInput::make('qa_staff_count')->label('Сотрудников QA')->numeric()->columnSpan(1),
                    ]),
                Section::make('Экологичность')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('eco_certified')->label('Экосертифицирована')->columnSpan(1),
                        Toggle::make('waste_recycling')->label('Переработка отходов')->columnSpan(1),
                        Toggle::make('water_treatment')->label('Очистка воды')->columnSpan(1),
                        Toggle::make('emission_control')->label('Контроль выбросов')->columnSpan(1),
                    ]),
                Section::make('Персонал')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_staff')->label('Всего сотрудников')->numeric()->columnSpan(1),
                        TextInput::make('management_staff')->label('Управленческих')->numeric()->columnSpan(1),
                        TextInput::make('technical_staff')->label('Технических')->numeric()->columnSpan(1),
                        TextInput::make('production_workers')->label('Производственных')->numeric()->columnSpan(1),
                    ]),
                Section::make('Сертификация')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TagsInput::make('certifications')->label('Сертификаты')->columnSpan(2),
                        DatePicker::make('last_audit_date')->label('Последний аудит')->columnSpan(1),
                        TextInput::make('audit_result')->label('Результат аудита')->columnSpan(1),
                    ]),
                Section::make('Рейтинг')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric(decimals:1)->max(5)->columnSpan(1),
                        TextInput::make('customer_satisfaction')->label('Удовлетворённость (%)')->numeric()->columnSpan(1),
                    ]),
                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('factory_image')->label('Фото')->image()->directory('manufacturing'),
                        FileUpload::make('gallery')->multiple()->image()->label('Галерея')->directory('manufacturing-gallery')->columnSpan('full'),
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
                ImageColumn::make('factory_image')->label('Фото')->size(40),
                TextColumn::make('factory_name')->label('Название')->searchable()->sortable()->weight('bold')->limit(30),
                TextColumn::make('industry')->label('Отрасль')->badge()->color('info'),
                TextColumn::make('city')->label('Город')->searchable(),
                TextColumn::make('rating')->label('Рейтинг')->numeric(decimals:1)->badge()->color('warning')->sortable(),
                TextColumn::make('workers_count')->label('Рабочих')->numeric(),
                TextColumn::make('daily_production_units')->label('Выпуск (шт/день)')->numeric()->badge()->color('success'),
                BadgeColumn::make('iso_9001_certified')->label('ISO 9001')->colors(['success'=>true,'gray'=>false]),
                BadgeColumn::make('eco_certified')->label('Эко')->colors(['info'=>true,'gray'=>false]),
                BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning'=>true,'gray'=>false]),
            ])->filters([
                SelectFilter::make('industry')->options(['textile'=>'Текстиль','machinery'=>'Машины','electronics'=>'Электроника']),
                Filter::make('iso')->query(fn(Builder $q)=>$q->where('iso_9001_certified',true))->label('ISO 9001'),
            ])->actions([ViewAction::make(),EditAction::make()])
                ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
                ->defaultSort('rating','desc');
        }

        public static function getPages(): array
        {
            return ['index'=>Pages\ListManufacturing::route('/'),'create'=>Pages\CreateManufacturing::route('/create'),'edit'=>Pages\EditManufacturing::route('/{record}/edit'),];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id',filament()->getTenant()->id);
        }
}
