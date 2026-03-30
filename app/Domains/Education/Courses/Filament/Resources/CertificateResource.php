<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CertificateResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Section, TextInput};
    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Table;

    final class CertificateResource extends Resource
    {
        protected static ?string $model = Certificate::class;
        protected static ?string $navigationIcon = 'heroicon-o-document-check';
        protected static ?string $navigationGroup = 'Обучение';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Сертификат')
                        ->schema([
                            TextInput::make('certificate_number')
                                ->label('Номер сертификата')
                                ->disabled(),
                            TextInput::make('student_name')
                                ->label('Имя студента')
                                ->disabled(),
                            TextInput::make('verification_code')
                                ->label('Код проверки')
                                ->disabled(),
                            TextInput::make('certificate_url')
                                ->label('URL сертификата')
                                ->url()
                                ->disabled(),
                        ]),
                    Section::make('Информация')
                        ->schema([
                            TextInput::make('student_id')
                                ->label('Студент')
                                ->disabled(),
                            TextInput::make('course_id')
                                ->label('Курс')
                                ->disabled(),
                            TextInput::make('issued_at')
                                ->label('Выдан')
                                ->disabled(),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('certificate_number')
                        ->label('Номер')
                        ->searchable(),
                    TextColumn::make('student_name')
                        ->label('Студент'),
                    TextColumn::make('course.title')
                        ->label('Курс'),
                    TextColumn::make('issued_at')
                        ->label('Выдан')
                        ->dateTime(),
                ]);
        }
}
