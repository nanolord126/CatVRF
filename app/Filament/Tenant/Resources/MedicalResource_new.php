<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = MedicalClinic::class;

        protected static ?string $navigationIcon = 'heroicon-o-heart';

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
                        TextInput::make('registration_number')->label('Лицензия')->required()->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('phone')->label('Телефон')->tel()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->columnSpan(1),
                        TextInput::make('website')->label('Веб-сайт')->url()->columnSpan(1),
                        Select::make('status')->label('Статус')->options(['draft' => 'Черновик', 'active' => 'Активна', 'inactive' => 'Неактивна'])->default('draft')->columnSpan(2),
                    ]),

                Section::make('Адрес')
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')->label('Адрес')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('city')->label('Город')->required()->columnSpan(1),
                        TextInput::make('zip_code')->label('Почтовый код')->columnSpan(1),
                        TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                        TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                    ]),

                Section::make('Описание')
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                        TagsInput::make('specializations')->label('Специализации')->columnSpan('full'),
                    ]),

                Section::make('Режим работы')
                    ->columns(2)
                    ->schema([
                        TextInput::make('working_hours')->label('Рабочие часы')->placeholder('09:00-18:00')->columnSpan(2),
                        TextInput::make('emergency_phone')->label('Скорая помощь')->tel()->columnSpan(1),
                        Toggle::make('has_emergency_service')->label('Служба скорой')->columnSpan(1),
                    ]),

                Section::make('Лицензирование')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        FileUpload::make('license_file')->label('Лицензия (PDF)')->acceptedFileTypes(['application/pdf'])->directory('medical-licenses')->columnSpan(1),
                        FileUpload::make('certificates')->label('Сертификаты')->multiple()->directory('medical-certs')->columnSpan(1),
                        DatePicker::make('license_expiry_date')->label('Срок действия')->columnSpan(1),
                        TextInput::make('license_issuer')->label('Выдавший орган')->maxLength(255)->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('logo')->label('Логотип')->image()->directory('medical-logos'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('medical-galleries')->columnSpan('full'),
                    ]),

                Section::make('SEO')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('meta_title')->label('Meta Title')->maxLength(60),
                        Textarea::make('meta_description')->label('Meta Description')->maxLength(160)->rows(2)->columnSpan(2),
                        TagsInput::make('keywords')->label('Ключевые слова')->columnSpan(2),
                    ]),

                Section::make('Управление')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label('Активна')->default(true),
                        Toggle::make('is_featured')->label('Избранная')->default(false),
                        Toggle::make('verified')->label('Проверена')->default(false),
                        TextInput::make('priority')->label('Приоритет')->numeric()->default(0)->columnSpan(2),
                        DatePicker::make('published_at')->label('Дата публикации')->columnSpan(1),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->label('Название')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('registration_number')->label('Лицензия')->badge()->color('info')->searchable(),
                TextColumn::make('city')->label('Город')->searchable()->sortable(),
                TextColumn::make('phone')->label('Телефон')->badge()->color('gray'),
                BadgeColumn::make('status')->label('Статус')->colors(['gray' => 'draft', 'success' => 'active', 'danger' => 'inactive'])->sortable(),
                BadgeColumn::make('verified')->label('Проверена')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('is_active')->label('Активна')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранная')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('priority')->label('Приоритет')->numeric()->sortable()->badge()->color('secondary'),
                TextColumn::make('website')->label('Веб-сайт')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('status')->options(['draft' => 'Черновик', 'active' => 'Активна', 'inactive' => 'Неактивна']),
                Filter::make('is_active')->query(fn (Builder $q) => $q->where('is_active', true))->label('Только активные'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListMedical::route('/'),
                'create' => Pages\CreateMedical::route('/create'),
                'edit' => Pages\EditMedical::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
