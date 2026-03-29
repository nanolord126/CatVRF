<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\BeautySalon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class BeautyResource extends Resource
{
    protected static ?string $model = BeautySalon::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Вертикали';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
            Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

            Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Название')->required()->maxLength(255)->columnSpan(2),
                    TextInput::make('salon_id')->label('ID')->unique(ignoreRecord: true)->columnSpan(1),
                    TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                    TextInput::make('email')->label('Email')->email()->columnSpan(1),
                    TextInput::make('website')->label('Веб-сайт')->url()->columnSpan(1),
                    Select::make('type')->label('Тип')->options(['salon' => 'Салон', 'studio' => 'Студия', 'spa' => 'СПА'])->required()->columnSpan(2),
                ]),

            Section::make('Адрес')
                ->columns(2)
                ->schema([
                    TextInput::make('address')->label('Адрес')->required()->columnSpan(2),
                    TextInput::make('city')->label('Город')->required()->columnSpan(1),
                    TextInput::make('zip_code')->label('Код')->columnSpan(1),
                    TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                    TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                ]),

            Section::make('Описание')
                ->schema([
                    Textarea::make('short_description')->label('Краткое')->maxLength(500)->rows(3),
                    RichEditor::make('full_description')->label('Полное')->maxLength(5000)->columnSpan('full'),
                    TagsInput::make('specializations')->label('Специализация')->columnSpan('full'),
                ]),

            Section::make('Режим работы')
                ->columns(2)
                ->schema([
                    TextInput::make('working_hours')->label('Часы')->placeholder('09:00-21:00')->columnSpan(2),
                    TextInput::make('weekend_hours')->label('Выходные')->columnSpan(1),
                    Toggle::make('open_weekends')->label('Открыто в выходные')->columnSpan(1),
                ]),

            Section::make('Команда')
                ->columns(2)
                ->schema([
                    TextInput::make('master_count')->label('Мастеров')->numeric()->columnSpan(1),
                    TextInput::make('employee_count')->label('Сотрудников')->numeric()->columnSpan(1),
                    Toggle::make('has_training')->label('Обучение')->columnSpan(2),
                ]),

            Section::make('Оборудование')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TagsInput::make('equipment')->label('Оборудование')->columnSpan(2),
                    TextInput::make('average_rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                    TextInput::make('review_count')->label('Отзывов')->numeric()->disabled()->columnSpan(1),
                ]),

            Section::make('Контакты')
                ->collapsed()
                ->columns(2)
                ->schema([
                    FileUpload::make('client_list')->label('Клиенты (Excel)')->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->directory('beauty-clients'),
                    TextInput::make('whatsapp')->label('WhatsApp')->tel()->columnSpan(1),
                    TextInput::make('telegram')->label('Telegram')->columnSpan(1),
                ]),

            Section::make('Медиа')
                ->collapsed()
                ->schema([
                    FileUpload::make('logo')->label('Логотип')->image()->directory('beauty-logos'),
                    FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('beauty-gallery')->columnSpan('full'),
                    FileUpload::make('certificate')->label('Сертификаты')->multiple()->directory('beauty-certs'),
                ]),

            Section::make('SEO')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('meta_title')->label('Title')->maxLength(60),
                    Textarea::make('meta_description')->label('Description')->maxLength(160)->rows(2)->columnSpan(2),
                    TagsInput::make('keywords')->label('Ключевые слова')->columnSpan(2),
                ]),

            Section::make('Управление')
                ->collapsed()
                ->columns(3)
                ->schema([
                    Toggle::make('is_active')->label('Активен')->default(true),
                    Toggle::make('is_featured')->label('Избранный')->default(false),
                    Toggle::make('verified')->label('Проверен')->default(false),
                    TextInput::make('priority')->label('Приоритет')->numeric()->columnSpan(2),
                    DatePicker::make('published_at')->label('Публикация')->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Салон')->searchable()->sortable()->weight('bold')->limit(35),
            TextColumn::make('city')->label('Город')->searchable(),
            TextColumn::make('type')->label('Тип')->badge()->color('primary'),
            TextColumn::make('phone')->label('Телефон')->badge()->color('gray')->limit(15),
            BadgeColumn::make('is_active')->label('Активен')->colors(['success' => true, 'gray' => false]),
            BadgeColumn::make('verified')->label('Проверен')->colors(['success' => true, 'gray' => false]),
            BadgeColumn::make('is_featured')->label('Избранный')->colors(['warning' => true]),
            TextColumn::make('average_rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('warning'),
            TextColumn::make('master_count')->label('Мастеров')->numeric()->badge()->color('secondary'),
            TextColumn::make('priority')->label('Приоритет')->numeric()->badge(),
            TextColumn::make('website')->label('Сайт')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('type')->options(['salon' => 'Салон', 'studio' => 'Студия', 'spa' => 'СПА']),
            Filter::make('is_active')->query(fn (Builder $q) => $q->where('is_active', true)),
        ])->actions([ViewAction::make(), EditAction::make()])
        ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
        ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBeauty::route('/'),
            'create' => Pages\CreateBeauty::route('/create'),
            'edit' => Pages\EditBeauty::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
