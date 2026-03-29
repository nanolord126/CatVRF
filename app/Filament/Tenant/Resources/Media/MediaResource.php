<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Media;
use App\Domains\Media\Models\MediaCompany;
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

final class MediaResource extends Resource
{
    protected static ?string $model = MediaCompany::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'Вертикали';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('tenant_id')->default(fn()=>filament()->getTenant()->id),
            Hidden::make('correlation_id')->default(fn()=>Str::uuid()->toString()),
            Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    TextInput::make('company_code')->label('Код')->unique(ignoreRecord:true)->columnSpan(1),
                    TextInput::make('company_name')->label('Название')->required()->columnSpan(1),
                    Select::make('type')->label('Тип')->options(['news'=>'Новости','magazine'=>'Журнал','podcast'=>'Подкаст','youtube'=>'YouTube','blog'=>'Блог','tv'=>'ТВ'])->required()->columnSpan(1),
                    TextInput::make('phone')->label('Телефон')->tel()->columnSpan(1),
                ]),
            Section::make('Контакты')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('email')->label('Email')->email()->columnSpan(1),
                    TextInput::make('website')->label('Сайт')->url()->columnSpan(1),
                    TextInput::make('address')->label('Адрес')->maxLength(500)->columnSpan(2),
                ]),
            Section::make('О компании')
                ->collapsed()
                ->schema([
                    Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                    RichEditor::make('full_description')->label('Полное описание')->columnSpan('full'),
                ]),
            Section::make('Аудитория и охват')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('monthly_readers')->label('Месячная аудитория')->numeric()->columnSpan(1),
                    TextInput::make('social_followers')->label('Подписчиков в соцсетях')->numeric()->columnSpan(1),
                    TextInput::make('newsletter_subscribers')->label('Подписчики новостей')->numeric()->columnSpan(1),
                    TextInput::make('monthly_page_views')->label('Page views (млн)')->numeric()->columnSpan(1),
                ]),
            Section::make('Контент')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('articles_per_month')->label('Статей/месяц')->numeric()->columnSpan(1),
                    TextInput::make('primary_category')->label('Основная категория')->columnSpan(1),
                    TagsInput::make('content_categories')->label('Категории контента')->columnSpan(2),
                    Toggle::make('has_video_content')->label('Видео контент')->columnSpan(1),
                    Toggle::make('has_podcasts')->label('Подкасты')->columnSpan(1),
                ]),
            Section::make('Команда')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('total_staff')->label('Всего сотрудников')->numeric()->columnSpan(1),
                    TextInput::make('journalists_count')->label('Журналистов')->numeric()->columnSpan(1),
                    TextInput::make('editors_count')->label('Редакторов')->numeric()->columnSpan(1),
                ]),
            Section::make('Публикации и награды')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('founded_year')->label('Основана')->numeric()->columnSpan(1),
                    TextInput::make('awards_count')->label('Награды')->numeric()->columnSpan(1),
                    TagsInput::make('awards')->label('Названия наград')->columnSpan(2),
                ]),
            Section::make('Партнёрства')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TagsInput::make('partners')->label('Партнёры')->columnSpan(2),
                    Toggle::make('has_sponsored_content')->label('Спонсируемый контент')->columnSpan(1),
                    TextInput::make('sponsored_content_rate')->label('Ставка (₽ за статью)')->numeric()->columnSpan(1),
                ]),
            Section::make('Медиа')
                ->collapsed()
                ->schema([
                    FileUpload::make('logo')->label('Логотип')->image()->directory('media-logos'),
                    FileUpload::make('banner')->label('Баннер')->image()->directory('media-banners'),
                    FileUpload::make('gallery')->multiple()->image()->label('Галерея')->directory('media-gallery')->columnSpan('full'),
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
            ImageColumn::make('logo')->label('Логотип')->size(40),
            TextColumn::make('company_name')->label('Название')->searchable()->sortable()->weight('bold')->limit(30),
            TextColumn::make('type')->label('Тип')->badge()->color('info'),
            TextColumn::make('monthly_readers')->label('Читатели')->numeric()->badge()->color('success'),
            TextColumn::make('social_followers')->label('Подписчиков')->numeric(),
            TextColumn::make('articles_per_month')->label('Статей/мес')->numeric(),
            TextColumn::make('journalists_count')->label('Журналистов')->numeric(),
            BadgeColumn::make('has_video_content')->label('Видео')->colors(['info'=>true,'gray'=>false]),
            BadgeColumn::make('has_podcasts')->label('Подкасты')->colors(['secondary'=>true,'gray'=>false]),
            BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning'=>true,'gray'=>false]),
        ])->filters([
            SelectFilter::make('type')->options(['news'=>'Новости','magazine'=>'Журнал','podcast'=>'Подкаст','youtube'=>'YouTube','blog'=>'Блог']),
            Filter::make('video')->query(fn(Builder $q)=>$q->where('has_video_content',true))->label('С видео'),
        ])->actions([ViewAction::make(),EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('monthly_readers','desc');
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListMedia::route('/'),'create'=>Pages\CreateMedia::route('/create'),'edit'=>Pages\EditMedia::route('/{record}/edit'),];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id',filament()->getTenant()->id);
    }
}
