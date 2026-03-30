<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Insurance;

use App\Models\Insurance;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class InsuranceResource extends Resource
{
    protected static ?string $model = Insurance::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')->columns(2)->schema([
                TextInput::make('policy_number')->label('Номер полиса')->required()->hidden(),
                TextInput::make('policy_name')->label('Название полиса')->required(),
                Select::make('insurance_type')->label('Тип страховки')->options([
                    'health' => 'Медицинская','auto' => 'Автомобильная','property' => 'Имущество','travel' => 'Путешествия',
                    'life' => 'Жизнь','liability' => 'Ответственность','business' => 'Бизнес','pet' => 'Домашние животные',
                ])->required(),
                TextInput::make('provider_name')->label('Страховщик')->required(),
                TextInput::make('provider_license')->label('Лицензия')->required(),
                TextInput::make('policy_term_years')->label('Срок (лет)')->numeric(),
            ]),
            Section::make('Описание')->schema([
                RichEditor::make('description')->label('Описание')->required()->columnSpanFull(),
                TextInput::make('short_description')->label('Краткое описание')->maxLength(255)->columnSpanFull(),
                TagsInput::make('coverage_items')->label('Виды покрытия')->required(),
            ])->columns(2),
            Section::make('Стоимость и условия')->columns(2)->schema([
                TextInput::make('base_premium')->label('Базовая премия')->numeric()->required(),
                TextInput::make('deductible')->label('Франшиза')->numeric(),
                TextInput::make('coverage_limit')->label('Лимит покрытия')->numeric(),
                TextInput::make('waiting_period_days')->label('Периода ожидания (дн)')->numeric(),
                TextInput::make('renewal_period_days')->label('Период продления (дн)')->numeric(),
            ]),
            Section::make('Исключения')->schema([
                Repeater::make('exclusions')->label('Исключения')->schema([
                    TextInput::make('exclusion_name')->label('Исключение')->required(),
                    RichEditor::make('exclusion_description')->label('Описание'),
                ])->columnSpanFull(),
            ]),
            Section::make('Условия и требования')->schema([
                TagsInput::make('eligibility_criteria')->label('Критерии приемлемости'),
                TextInput::make('age_minimum')->label('Минимальный возраст')->numeric(),
                TextInput::make('age_maximum')->label('Максимальный возраст')->numeric(),
                Toggle::make('pre_existing_allowed')->label('Разрешены существующие условия'),
                Toggle::make('international_coverage')->label('Международное покрытие'),
            ])->columns(2),
            Section::make('Рейтинг и отзывы')->columns(2)->schema([
                TextInput::make('rating')->label('Рейтинг')->numeric()->disabled(),
                TextInput::make('claims_approved_percent')->label('Одобрено %')->numeric(),
                TextInput::make('avg_claim_payout_days')->label('Средний срок выплаты (дн)')->numeric(),
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
            TextColumn::make('policy_name')->label('Полис')->searchable()->sortable(),
            BadgeColumn::make('insurance_type')->label('Тип'),
            TextColumn::make('provider_name')->label('Страховщик'),
            TextColumn::make('base_premium')->label('Премия')->numeric(),
            TextColumn::make('coverage_limit')->label('Лимит'),
            TextColumn::make('rating')->label('Рейтинг')->numeric()->badge('warning'),
            TextColumn::make('claims_approved_percent')->label('Одобрено %'),
            ToggleColumn::make('is_active')->label('Активна'),
        ])->defaultSort('policy_name');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        DB::transaction(function()use(&$data){
            $data['correlation_id'] = Str::uuid()->toString();
            Log::channel('audit')->info('Insurance policy action',['user'=>auth()->id(),'correlation_id'=>$data['correlation_id']]);
        });
        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}
