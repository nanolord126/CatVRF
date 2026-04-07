<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Marketing;

    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables\Columns\{BadgeColumn,TextColumn,ImageColumn};
    use Filament\Tables\Actions\{BulkActionGroup,DeleteBulkAction,EditAction,ViewAction};
    use Filament\Tables\Filters\{Filter,SelectFilter};
    use Filament\Tables\Table;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;

    final class MarketingResource extends Resource
    {
        protected static ?string $model = Campaign::class;
        protected static ?string $navigationIcon = 'heroicon-o-megaphone';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn()=>filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn()=>Str::uuid()->toString()),
                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('campaign_code')->label('Код')->unique(ignoreRecord:true)->columnSpan(1),
                        TextInput::make('campaign_name')->label('Название')->required()->columnSpan(1),
                        Select::make('type')->label('Тип')->options(['email'=>'Email','sms'=>'SMS','social'=>'Соцсети','banner'=>'Баннеры','video'=>'Видео','influencer'=>'Инфлюэнсеры'])->required()->columnSpan(1),
                        Select::make('status')->label('Статус')->options(['planning'=>'Планирование','active'=>'Активная','paused'=>'Приостановлена','completed'=>'Завершена'])->columnSpan(1),
                    ]),
                Section::make('Описание')
                    ->collapsed()
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->columnSpan('full'),
                    ]),
                Section::make('Сроки')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        DatePicker::make('campaign_start_date')->label('Начало')->required()->columnSpan(1),
                        DatePicker::make('campaign_end_date')->label('Конец')->required()->columnSpan(1),
                        TextInput::make('duration_days')->label('Длительность (дней)')->numeric()->columnSpan(1),
                    ]),
                Section::make('Целевая аудитория')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('target_age_min')->label('Возраст от')->numeric()->columnSpan(1),
                        TextInput::make('target_age_max')->label('Возраст до')->numeric()->columnSpan(1),
                        TagsInput::make('target_locations')->label('Целевые города')->columnSpan(2),
                        TagsInput::make('target_interests')->label('Интересы')->columnSpan(2),
                    ]),
                Section::make('Бюджет')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_budget')->label('Общий бюджет (₽)')->numeric()->columnSpan(1),
                        TextInput::make('spent_budget')->label('Потрачено (₽)')->numeric()->columnSpan(1),
                        TextInput::make('daily_budget')->label('Дневной бюджет (₽)')->numeric()->columnSpan(1),
                    ]),
                Section::make('Цели кампании')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Select::make('primary_goal')->label('Основная цель')->options(['awareness'=>'Осведомление','consideration'=>'Рассмотрение','conversion'=>'Конверсия','retention'=>'Удержание'])->columnSpan(1),
                        TextInput::make('target_conversion')->label('Целевая конверсия (%)')->numeric()->columnSpan(1),
                        TextInput::make('target_roi')->label('Целевой ROI (%)')->numeric()->columnSpan(1),
                    ]),
                Section::make('Результаты')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('impressions')->label('Показы')->numeric()->columnSpan(1),
                        TextInput::make('clicks')->label('Клики')->numeric()->columnSpan(1),
                        TextInput::make('conversions')->label('Конверсии')->numeric()->columnSpan(1),
                        TextInput::make('actual_roi')->label('Реальный ROI (%)')->numeric()->columnSpan(1),
                    ]),
                Section::make('Каналы')
                    ->collapsed()
                    ->schema([
                        Repeater::make('channels')->label('Каналы распространения')
                            ->schema([
                                Select::make('channel_name')->label('Канал')->options(['facebook'=>'Facebook','instagram'=>'Instagram','google'=>'Google','email'=>'Email','sms'=>'SMS'])->required(),
                                TextInput::make('channel_budget')->label('Бюджет (₽)')->numeric(),
                                TextInput::make('allocated_percent')->label('% бюджета')->numeric(),
                            ])->columnSpan('full'),
                    ]),
                Section::make('Контент')
                    ->collapsed()
                    ->schema([
                        TextInput::make('primary_message')->label('Основное сообщение')->maxLength(500),
                        TextInput::make('cta_text')->label('CTA текст')->columnSpan('full'),
                        Textarea::make('content_notes')->label('Заметки по контенту')->maxLength(1000)->rows(3)->columnSpan('full'),
                    ]),
                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('campaign_banner')->label('Баннер')->image()->directory('marketing-banners'),
                        FileUpload::make('creative_assets')->label('Творческие активы')->multiple()->image()->directory('marketing-assets')->columnSpan('full'),
                        FileUpload::make('video')->label('Видео')->acceptedFileTypes(['video/*'])->directory('marketing-videos'),
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
                ImageColumn::make('campaign_banner')->label('Баннер')->size(40),
                TextColumn::make('campaign_name')->label('Название')->searchable()->sortable()->weight('bold')->limit(30),
                TextColumn::make('type')->label('Тип')->badge()->color('info'),
                TextColumn::make('status')->label('Статус')->badge()->color(fn($state)=>$state==='active'?'success':'secondary'),
                TextColumn::make('campaign_start_date')->label('Начало')->date()->sortable(),
                TextColumn::make('total_budget')->label('Бюджет (₽)')->numeric()->badge()->color('success'),
                TextColumn::make('actual_roi')->label('ROI (%)')->numeric(),
                BadgeColumn::make('is_active')->label('Активна')->colors(['success'=>true,'gray'=>false]),
                BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning'=>true,'gray'=>false]),
                TextColumn::make('conversions')->label('Конверсии')->numeric()->toggleable(isToggledHiddenByDefault:true),
            ])->filters([
                SelectFilter::make('type')->options(['email'=>'Email','sms'=>'SMS','social'=>'Соцсети','banner'=>'Баннеры']),
                Filter::make('active')->query(fn(Builder $q)=>$q->where('status','active'))->label('Активные'),
            ])->actions([ViewAction::make(),EditAction::make()])
                ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
                ->defaultSort('campaign_start_date','desc');
        }

        public static function getPages(): array
        {
            return ['index'=>Pages\ListMarketing::route('/'),'create'=>Pages\CreateMarketing::route('/create'),'edit'=>Pages\EditMarketing::route('/{record}/edit'),];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id',filament()->getTenant()->id);
        }
}
