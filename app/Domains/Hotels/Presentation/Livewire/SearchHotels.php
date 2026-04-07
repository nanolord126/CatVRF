<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Presentation\Livewire;

use App\Domains\Hotels\Application\UseCases\B2C\SearchHotelsUseCase;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * SearchHotels — Livewire-компонент поиска отелей (B2C).
 *
 * Обеспечивает реалтайм поиск отелей с параметрами:
 * город, даты, количество гостей, ценовой диапазон, сортировка.
 * Сохраняет параметры поиска в URL (атрибут #[Url]).
 * Отображает состояние загрузки и ошибки.
 *
 * @package App\Domains\Hotels\Presentation\Livewire
 */
final class SearchHotels extends Component
{
    use WithPagination;

    // ===== Параметры поиска (сохраняются в URL) =====

    #[Url(as: 'city')]
    private string $city = '';

    #[Url(as: 'check_in')]
    private string $checkInDate = '';

    #[Url(as: 'check_out')]
    private string $checkOutDate = '';

    #[Url(as: 'guests')]
    private int $capacity = 1;

    #[Url(as: 'sort')]
    private string $sortBy = 'price_asc';

    // ===== Фильтры =====

    private int $minPrice = 0;
    private int $maxPrice = 0;
    private float $minRating = 0.0;
    private array $selectedAmenities = [];

    // ===== Состояние =====

    private bool $isLoading = false;
    private bool $hasSearched = false;
    private string $errorMessage = '';
    private Collection $hotels;
    private int $totalFound = 0;

    /**
     * Доступные значения сортировки.
     *
     * @var array<string, string>
     */
    private const SORT_OPTIONS = [
        'price_asc'    => 'Цена: по возрастанию',
        'price_desc'   => 'Цена: по убыванию',
        'rating_desc'  => 'Рейтинг: высокий',
        'rating_asc'   => 'Рейтинг: низкий',
    ];

    /**
     * Правила валидации формы поиска.
     *
     * @var array<string, string|array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'city'         => ['required', 'string', 'min:2', 'max:255'],
            'checkInDate'  => ['required', 'date', 'after_or_equal:today'],
            'checkOutDate' => ['required', 'date', 'after:checkInDate'],
            'capacity'     => ['required', 'integer', 'min:1', 'max:50'],
            'minPrice'     => ['nullable', 'integer', 'min:0'],
            'maxPrice'     => ['nullable', 'integer', 'min:0'],
            'minRating'    => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sortBy'       => ['required', 'string', 'in:price_asc,price_desc,rating_desc,rating_asc'],
        ];
    }

    /**
     * Сообщения валидации.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'city.required'        => 'Укажите город.',
            'city.min'             => 'Название города должно быть не менее 2 символов.',
            'checkInDate.required' => 'Укажите дату заезда.',
            'checkInDate.after_or_equal' => 'Дата заезда не может быть в прошлом.',
            'checkOutDate.required'=> 'Укажите дату выезда.',
            'checkOutDate.after'   => 'Дата выезда должна быть позже даты заезда.',
            'capacity.required'    => 'Укажите количество гостей.',
            'sortBy.in'            => 'Неверный параметр сортировки.',
        ];
    }

    /**
     * Инициализация компонента.
     * Устанавливает даты по умолчанию и пустую коллекцию отелей.
     */
    public function mount(): void
    {
        $this->hotels       = new Collection();
        $this->checkInDate  = Carbon::now()->format('Y-m-d');
        $this->checkOutDate = Carbon::now()->addDay()->format('Y-m-d');
    }

    /**
     * Выполняет поиск отелей через Use Case.
     * Обрабатывает ошибки и отображает состояние загрузки.
     */
    public function search(SearchHotelsUseCase $searchHotelsUseCase): void
    {
        $this->validate();
        $this->isLoading    = true;
        $this->errorMessage = '';
        $this->resetPage();

        try {
            $criteria = [
                'city'           => $this->city,
                'check_in_date'  => $this->checkInDate,
                'check_out_date' => $this->checkOutDate,
                'capacity'       => $this->capacity,
                'sort_by'        => $this->sortBy,
                'min_price'      => $this->minPrice > 0 ? $this->minPrice : null,
                'max_price'      => $this->maxPrice > 0 ? $this->maxPrice : null,
                'rating'         => $this->minRating > 0 ? $this->minRating : null,
                'amenities'      => $this->selectedAmenities ?: null,
            ];

            $this->hotels     = $searchHotelsUseCase->execute(array_filter($criteria, fn($v) => $v !== null));
            $this->totalFound = $this->hotels->count();
            $this->hasSearched = true;
        } catch (\Throwable $e) {
            $this->errorMessage = 'Произошла ошибка при поиске. Попробуйте позже.';
            $this->hotels      = new Collection();
            $this->totalFound  = 0;
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Сбрасывает все параметры поиска в значения по умолчанию.
     */
    public function resetSearch(): void
    {
        $this->city              = '';
        $this->checkInDate       = Carbon::now()->format('Y-m-d');
        $this->checkOutDate      = Carbon::now()->addDay()->format('Y-m-d');
        $this->capacity          = 1;
        $this->sortBy            = 'price_asc';
        $this->minPrice          = 0;
        $this->maxPrice          = 0;
        $this->minRating         = 0.0;
        $this->selectedAmenities = [];
        $this->hotels            = new Collection();
        $this->totalFound        = 0;
        $this->hasSearched       = false;
        $this->errorMessage      = '';
        $this->resetPage();
    }

    /**
     * Изменяет сортировку и автоматически перезапускает поиск через dispatch.
     */
    public function updatedSortBy(string $value): void
    {
        $this->sortBy = $value;
        $this->dispatch('sort-changed', sort: $value);
    }

    /**
     * Возвращает массив вариантов сортировки для передачи в представление.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function sortOptions(): array
    {
        return self::SORT_OPTIONS;
    }

    /**
     * Отрисовывает Blade-шаблон компонента.
     */
    public function render(): View
    {
        return view('livewire.hotels.search-hotels');
    }
}
