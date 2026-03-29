<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Medical\Models\MedicalRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * MedicalRecordResource Resource
 * 
 * Production-ready Filament 3.x Resource
 * КАНОН 2026 compliant
 */
final class MedicalRecordResource extends Resource
{
    protected static ?string $model = MedicalRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Medical Platform';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->description('Базовые сведения')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Название')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                TextInput::make('slug')
                                    ->label('Идентификатор')
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(1),

                                Select::make('status')
                                    ->label('Статус')
                                    ->options([
                                        'draft' => 'Черновик',
                                        'published' => 'Опубликовано',
                                        'archived' => 'Архив',
                                    ])
                                    ->default('draft')
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Описание')
                    ->icon('heroicon-m-document-text')
                    ->schema([
                        Textarea::make('description')
                            ->label('Описание')
                            ->maxLength(1000)
                            ->rows(4),

                        RichEditor::make('content')
                            ->label('Содержимое')
                            ->columnSpan('full')
                            ->maxLength(5000),
                    ]),

                Section::make('Медиа')
                    ->icon('heroicon-m-photo')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('image')
                            ->label('Изображение')
                            ->image()
                            ->directory('resources'),

                        FileUpload::make('attachments')
                            ->label('Файлы')
                            ->multiple()
                            ->directory('attachments')
                            ->columnSpan('full'),
                    ]),

                Section::make('Настройки')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Активно')
                            ->default(true),

                        Toggle::make('is_featured')
                            ->label('Избранное')
                            ->default(false),

                        TextInput::make('priority')
                            ->label('Приоритет')
                            ->numeric()
                            ->default(0),

                        DatePicker::make('published_at')
                            ->label('Дата публикации'),

                        TagsInput::make('tags')
                            ->label('Теги')
                            ->columnSpan('full'),
                    ]),
            ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListMedicalRecord::route('/'),
            'create' => Pages\\CreateMedicalRecord::route('/create'),
            'edit' => Pages\\EditMedicalRecord::route('/{record}/edit'),
            'view' => Pages\\ViewMedicalRecord::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListMedicalRecord::route('/'),
            'create' => Pages\\CreateMedicalRecord::route('/create'),
            'edit' => Pages\\EditMedicalRecord::route('/{record}/edit'),
            'view' => Pages\\ViewMedicalRecord::route('/{record}'),
        ];
    }
}
