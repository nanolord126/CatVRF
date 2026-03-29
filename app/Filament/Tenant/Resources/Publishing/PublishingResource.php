<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Publishing;

use App\Models\Publishing;
use Filament\Forms\Components\{DatePicker,FileUpload,Grid,Repeater,RichEditor,Section,Select,TagsInput,TextInput,Toggle};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{BadgeColumn,ImageColumn,TextColumn,ToggleColumn};
use Filament\Tables\Table;
use Illuminate\Support\Facades\{Log,DB};
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

final class PublishingResource extends Resource
{
    protected static ?string $model = Publishing::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('publisher_code')->label('Код издателя')->required()->hidden(),
                TextInput::make('publisher_name')->label('Название издателя')->required(),
                Select::make('publisher_type')->label('Тип издателя')->options([
                    'traditional' => 'Традиционное издательство','indie' => 'Инди-издатель','hybrid' => 'Гибридное',
                    'academic' => 'Академическое','medical' => 'Медицинское','educational' => 'Образовательное',
                ])->required(),
                TextInput::make('phone')->label('Телефон')->tel()->required(),
                TextInput::make('email')->label('Email')->email()->required(),
                FileUpload::make('logo')->label('Логотип')->image()->directory('publishing'),
            ]),
            Section::make('Адрес')->columns(2)->schema([
                TextInput::make('address')->label('Адрес')->required()->columnSpanFull(),
                TextInput::make('city')->label('Город')->required(),
                TextInput::make('website')->label('Веб-сайт')->url(),
            ]),
            Section::make('Описание')->schema([
                RichEditor::make('description')->label('Описание')->columnSpanFull(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Section::make('Специализация')->columns(2)->schema([
                TagsInput::make('genres')->label('Жанры')->required(),
                TextInput::make('year_founded')->label('Год основания')->numeric(),
                TextInput::make('books_published_per_year')->label('Книг в год')->numeric(),
                Toggle::make('publishes_international')->label('Публикует иностранные книги'),
            ]),
            Section::make('Каталог')->columns(2)->schema([
                TextInput::make('total_titles')->label('Всего названий')->numeric(),
                TextInput::make('in_print_titles')->label('В продаже')->numeric(),
                TextInput::make('ebooks_count')->label('Электронных книг')->numeric(),
                TextInput::make('audiobooks_count')->label('Аудиокниг')->numeric(),
            ]),
            Section::make('Авторы и сотрудники')->columns(2)->schema([
                TextInput::make('total_authors')->label('Авторов')->numeric(),
                TextInput::make('active_authors')->label('Активных авторов')->numeric(),
                TextInput::make('staff_count')->label('Сотрудников')->numeric(),
                TextInput::make('editors_count')->label('Редакторов')->numeric(),
            ]),
            Section::make('Распределение и продажи')->columns(2)->schema([
                Toggle::make('has_bookstore_distribution')->label('Распредел в книжных магазинах'),
                Toggle::make('has_online_sales')->label('Онлайн продажи'),
                Toggle::make('has_international_distribution')->label('Международное распределение'),
                TextInput::make('sales_channels')->label('Каналы продаж'),
            ]),
            Section::make('Награды и признание')->columns(2)->schema([
                TagsInput::make('awards')->label('Премии'),
                TextInput::make('award_count')->label('Всего премий')->numeric(),
                TextInput::make('bestseller_count')->label('Бестселлеров')->numeric(),
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
            ImageColumn::make('logo')->label('Логотип')->square()->size(40),
            TextColumn::make('publisher_name')->label('Издатель')->searchable()->sortable(),
            BadgeColumn::make('publisher_type')->label('Тип'),
            TextColumn::make('total_titles')->label('Названий')->numeric(),
            TextColumn::make('books_published_per_year')->label('Книг/год'),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            TextColumn::make('award_count')->label('Премий'),
            ToggleColumn::make('is_active')->label('Активен'),
        ])->defaultSort('publisher_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        DB::transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            Log::channel('audit')->info('Publishing action',['user'=>auth()->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
