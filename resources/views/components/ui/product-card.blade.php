{{--
    x-ui-product-card — карточка товара/услуги маркетплейса.
    Канон: товары без наличия — grayscale + без кнопки «В корзину».
    Пропсы:
      product     : array|object  — { id, name, image, price, b2bPrice, inStock, rating, reviewsCount }
      isB2B       : bool  (default: false)
      vertical    : string  — slug вертикали (beauty, food, furniture, ...)
--}}
@props([
    'product'  => null,
    'isB2B'    => false,
    'vertical' => 'marketplace',
])

@php
/** @var object|array $product */
$id           = data_get($product, 'id');
$name         = data_get($product, 'name', 'Товар');
$image        = data_get($product, 'image');
$price        = data_get($product, 'price', 0);
$b2bPrice     = data_get($product, 'b2b_price');
$inStock      = (bool) data_get($product, 'in_stock', true);
$rating       = data_get($product, 'rating');
$reviewsCount = data_get($product, 'reviews_count', 0);
@endphp

<article
    {{ $attributes->merge(['class' => 'group relative rounded-marketplace shadow-card overflow-hidden bg-black/30 dark:bg-black/40 border border-white/5 transition-all duration-200 hover:shadow-modal hover:-translate-y-0.5']) }}
    aria-label="{{ $name }}"
>
    {{-- Изображение --}}
    <div class="relative aspect-square overflow-hidden bg-carbon-900">
        @if($image)
            <img
                src="{{ $image }}"
                alt="{{ $name }}"
                loading="lazy"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105
                    {{ !$inStock ? 'grayscale opacity-60' : '' }}"
            />
        @else
            <div class="flex items-center justify-center w-full h-full bg-carbon-900 {{ !$inStock ? 'opacity-60' : '' }}">
                <svg class="w-12 h-12 text-carbon-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif

        @if(!$inStock)
            <div class="absolute inset-0 flex items-center justify-center">
                <x-ui-badge variant="neutral" class="text-xs font-bold">Нет в наличии</x-ui-badge>
            </div>
        @endif
    </div>

    {{-- Контент --}}
    <div class="p-3 space-y-2">
        <h3 class="text-sm font-medium text-carbon-100 line-clamp-2 leading-snug">
            {{ $name }}
        </h3>

        {{-- Рейтинг --}}
        @if($rating)
            <div class="flex items-center gap-1" aria-label="Рейтинг: {{ $rating }} из 5">
                <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span class="text-xs text-carbon-400">{{ number_format($rating, 1) }} ({{ $reviewsCount }})</span>
            </div>
        @endif

        {{-- Цена --}}
        <div class="flex items-center justify-between gap-2">
            <x-ui-price :amount="$price" :b2bAmount="$b2bPrice" :isB2B="$isB2B" :inStock="$inStock" size="md"/>

            @if($inStock)
                <livewire:cart.add-to-cart :productId="$id" :vertical="$vertical" :key="'cart-btn-'.$id"/>
            @endif
        </div>
    </div>
</article>
