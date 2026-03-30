<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = PetClinic::class;
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
                        TextInput::make('name')->label('Название клиники')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('clinic_id')->label('ID клиники')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->required()->columnSpan(1),
                        TextInput::make('website')->label('Веб-сайт')->url()->columnSpan(1),
                        Select::make('type')->label('Тип')->options(['clinic' => 'Клиника', 'grooming' => 'Груминг', 'boarding' => 'Передержка', 'shop' => 'Магазин'])->required()->columnSpan(2),
                    ]),

                Section::make('Адрес и геолокация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')->label('Адрес')->required()->columnSpan(2),
                        TextInput::make('city')->label('Город')->required()->columnSpan(1),
                        TextInput::make('zip_code')->label('Почтовый код')->columnSpan(1),
                        TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                        TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                    ]),

                Section::make('Описание услуг')
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                        TagsInput::make('specializations')->label('Специализация')->columnSpan('full'),
                    ]),

                Section::make('Специализация животные')
                    ->columns(2)
                    ->schema([
                        Toggle::make('accepts_dogs')->label('Собаки')->columnSpan(1),
                        Toggle::make('accepts_cats')->label('Кошки')->columnSpan(1),
                        Toggle::make('accepts_birds')->label('Птицы')->columnSpan(1),
                        Toggle::make('accepts_exotic')->label('Экзотические животные')->columnSpan(1),
                        Toggle::make('accepts_small_animals')->label('Грызуны')->columnSpan(2),
                    ]),

                Section::make('Услуги и оборудование')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TagsInput::make('services')->label('Услуги')->columnSpan(2),
                        TagsInput::make('equipment')->label('Оборудование')->columnSpan(2),
                        TextInput::make('vet_count')->label('Количество ветеринаров')->numeric()->columnSpan(1),
                        TextInput::make('groomer_count')->label('Количество грумеров')->numeric()->columnSpan(1),
                    ]),

                Section::make('Режим работы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('working_hours')->label('Рабочие часы')->placeholder('09:00-18:00')->columnSpan(2),
                        TextInput::make('emergency_phone')->label('Скорая помощь')->tel()->columnSpan(1),
                        Toggle::make('has_emergency_service')->label('Служба скорой 24/7')->columnSpan(1),
                    ]),

                Section::make('Передержка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_boarding')->label('Услуга передержки')->columnSpan(2),
                        TextInput::make('boarding_capacity')->label('Вместимость')->numeric()->columnSpan(1),
                        TextInput::make('boarding_price_per_day')->label('Цена/сутки (₽)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Документы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        FileUpload::make('license')->label('Лицензия (PDF)')->acceptedFileTypes(['application/pdf'])->directory('pet-licenses')->columnSpan(1),
                        FileUpload::make('certificates')->label('Сертификаты')->multiple()->directory('pet-certs')->columnSpan(1),
                        DatePicker::make('license_expiry')->label('Срок действия')->columnSpan(2),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('logo')->label('Логотип')->image()->directory('pet-logos'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('pet-gallery')->columnSpan('full'),
                    ]),

                Section::make('Контакты')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('whatsapp')->label('WhatsApp')->tel()->columnSpan(1),
                        TextInput::make('telegram')->label('Telegram')->columnSpan(1),
                        FileUpload::make('client_list')->label('Список клиентов (Excel)')->directory('pet-clients'),
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
                        TextInput::make('priority')->label('Приоритет')->numeric()->columnSpan(2),
                        DatePicker::make('published_at')->label('Публикация')->columnSpan(1),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->label('Клиника')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('city')->label('Город')->searchable()->sortable(),
                TextColumn::make('type')->label('Тип')->badge()->color('primary'),
                TextColumn::make('phone')->label('Телефон')->badge()->color('gray')->limit(18),
                BadgeColumn::make('is_active')->label('Активна')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('verified')->label('Проверена')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранная')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('vet_count')->label('Ветеринаров')->numeric()->badge()->color('secondary'),
                TextColumn::make('has_emergency_service')->label('Скорая 24/7')->badge()->color('danger'),
                TextColumn::make('priority')->label('Приоритет')->numeric()->sortable()->badge(),
                TextColumn::make('website')->label('Веб-сайт')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('type')->options(['clinic' => 'Клиника', 'grooming' => 'Груминг', 'boarding' => 'Передержка', 'shop' => 'Магазин']),
                Filter::make('is_active')->query(fn (Builder $q) => $q->where('is_active', true)),
                Filter::make('has_emergency')->query(fn (Builder $q) => $q->where('has_emergency_service', true))->label('Со скорой помощью'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListPet::route('/'),
                'create' => Pages\CreatePet::route('/create'),
                'edit' => Pages\EditPet::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
