<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Bloggers\Models\BloggerProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;

final class BloggerProfileResource extends Resource
{
    protected static ?string $model = BloggerProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Профили блогеров';
    protected static ?string $pluralModelLabel = 'Профили блогеров';
    protected static ?string $modelLabel = 'Профиль блогера';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('user.name')
                            ->label('Имя пользователя')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('user.email')
                            ->label('Email')
                            ->email()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('display_name')
                            ->label('Имя для отображения')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan(1),

                        TextInput::make('inn')
                            ->label('ИНН')
                            ->regex('/^\d{10,12}$/')
                            ->disabled()
                            ->columnSpan(1),

                        Textarea::make('bio')
                            ->label('Биография')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpan('full'),
                    ])->columns(2),

                Section::make('Профиль')
                    ->schema([
                        FileUpload::make('profile_picture')
                            ->label('Фото профиля')
                            ->image()
                            ->avatar()
                            ->columnSpan(1),

                        TextInput::make('website')
                            ->label('Веб-сайт')
                            ->url()
                            ->columnSpan(1),

                        TextInput::make('instagram')
                            ->label('Instagram')
                            ->url()
                            ->columnSpan(1),

                        TextInput::make('tiktok')
                            ->label('TikTok')
                            ->url()
                            ->columnSpan(1),

                        Select::make('category')
                            ->label('Категория')
                            ->options([
                                'beauty' => 'Красота',
                                'fashion' => 'Мода',
                                'food' => 'Еда',
                                'travel' => 'Путешествия',
                                'fitness' => 'Фитнес',
                                'gaming' => 'Игры',
                                'education' => 'Образование',
                                'lifestyle' => 'Образ жизни',
                                'other' => 'Другое',
                            ])
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Верификация')
                    ->schema([
                        Select::make('verification_status')
                            ->label('Статус верификации')
                            ->options([
                                'pending' => 'На рассмотрении',
                                'verified' => 'Верифицирован',
                                'rejected' => 'Отклонен',
                                'suspended' => 'Приостановлен',
                            ])
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('verified_at')
                            ->label('Дата верификации')
                            ->disabled()
                            ->columnSpan(1),

                        Textarea::make('rejection_reason')
                            ->label('Причина отклонения')
                            ->rows(2)
                            ->columnSpan('full')
                            ->visible(fn (Forms\Get $get) => $get('verification_status') === 'rejected'),

                        FileUpload::make('verification_documents')
                            ->label('Документы верификации')
                            ->multiple()
                            ->maxSize(10240)
                            ->disabled()
                            ->columnSpan('full'),
                    ])->columns(2),

                Section::make('Кошелёк')
                    ->schema([
                        TextInput::make('wallet_balance')
                            ->label('Баланс кошелька (копейки)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('bank_account')
                            ->label('Банковский счёт')
                            ->columnSpan(1),

                        TextInput::make('bank_name')
                            ->label('Название банка')
                            ->columnSpan(1),

                        TextInput::make('bank_bik')
                            ->label('БИК')
                            ->regex('/^\d{9}$/')
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Статистика')
                    ->schema([
                        TextInput::make('total_streams')
                            ->label('Всего потоков')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('total_followers')
                            ->label('Всего фолловеров')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('average_viewers')
                            ->label('Среднее зрителей')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(2),

                Section::make('Модерация')
                    ->schema([
                        Select::make('moderation_status')
                            ->label('Статус модерации')
                            ->options([
                                'active' => 'Активен',
                                'warned' => 'Предупреждение',
                                'suspended' => 'Приостановлен',
                                'banned' => 'Заблокирован',
                            ])
                            ->columnSpan(1),

                        Textarea::make('moderation_notes')
                            ->label('Заметки модератора')
                            ->rows(3)
                            ->columnSpan('full'),

                        Select::make('is_featured')
                            ->label('Рекомендуемый блогер')
                            ->boolean()
                            ->columnSpan(1),

                        TextInput::make('featured_until')
                            ->label('Рекомендуемый до')
                            ->type('datetime-local')
                            ->columnSpan(1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_picture')
                    ->label('Фото')
                    ->circular()
                    ->size(40),

                TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label('Имя для отображения')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('verification_status')
                    ->label('Верификация')
                    ->colors([
                        'primary' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                        'warning' => 'suspended',
                    ])
                    ->sortable(),

                BadgeColumn::make('moderation_status')
                    ->label('Модерация')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'warned',
                        'danger' => 'suspended',
                        'gray' => 'banned',
                    ])
                    ->sortable(),

                TextColumn::make('total_streams')
                    ->label('Потоков')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_followers')
                    ->label('Фолловеров')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Дата регистрации')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('Верификация')
                    ->options([
                        'pending' => 'На рассмотрении',
                        'verified' => 'Верифицирован',
                        'rejected' => 'Отклонен',
                        'suspended' => 'Приостановлен',
                    ]),

                Tables\Filters\SelectFilter::make('moderation_status')
                    ->label('Модерация')
                    ->options([
                        'active' => 'Активен',
                        'warned' => 'Предупреждение',
                        'suspended' => 'Приостановлен',
                        'banned' => 'Заблокирован',
                    ]),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Рекомендуемый'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('verify')
                    ->label('Верифицировать')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (BloggerProfile $record) {
                        $record->update([
                            'verification_status' => 'verified',
                            'verified_at' => now(),
                        ]);
                    })
                    ->visible(fn (BloggerProfile $record) => $record->verification_status === 'pending'),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Причина отклонения')
                            ->required(),
                    ])
                    ->action(function (BloggerProfile $record, array $data) {
                        $record->update([
                            'verification_status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                    })
                    ->visible(fn (BloggerProfile $record) => $record->verification_status === 'pending'),
                Tables\Actions\Action::make('suspend')
                    ->label('Приостановить')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (BloggerProfile $record) {
                        $record->update(['moderation_status' => 'suspended']);
                    }),
                Tables\Actions\Action::make('ban')
                    ->label('Заблокировать')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (BloggerProfile $record) {
                        $record->update(['moderation_status' => 'banned']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\BloggerProfileResource\Pages\ListBloggerProfiles::route('/'),
            'create' => \App\Filament\Tenant\Resources\BloggerProfileResource\Pages\CreateBloggerProfile::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\BloggerProfileResource\Pages\EditBloggerProfile::route('/{record}/edit'),
        ];
    }
}
