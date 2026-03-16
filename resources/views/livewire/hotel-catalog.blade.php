<div class="min-h-screen bg-gray-50 py-8">
	<div class="max-w-7xl mx-auto px-4">
		<!-- Header -->
		<div class="mb-8">
			<h1 class="text-4xl font-bold text-gray-900 mb-2">Каталог отелей</h1>
			<p class="text-gray-600">Найдите идеальное место для проживания</p>
		</div>

		<!-- Filters Section -->
		<div class="bg-white rounded-lg shadow-md p-6 mb-8">
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
				<!-- Search -->
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-2">
						Поиск
					</label>
					<input type="text" wire:model.live.debounce.500ms="search"
						placeholder="Название, адрес..."
						class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<!-- Category Filter -->
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-2">
						Категория
					</label>
					<select wire:model.live="category"
						class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
						<option value="">Все категории</option>
						@foreach ($categories as $key => $label)
							<option value="{{ $key }}">{{ $label }}</option>
						@endforeach
					</select>
				</div>

				<!-- Rating Filter -->
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-2">
						Минимальная оценка
					</label>
					<select wire:model.live="minRating"
						class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
						<option value="">Любая</option>
						<option value="3">⭐⭐⭐ (3.0+)</option>
						<option value="3.5">⭐⭐⭐ (3.5+)</option>
						<option value="4">⭐⭐⭐⭐ (4.0+)</option>
						<option value="4.5">⭐⭐⭐⭐ (4.5+)</option>
					</select>
				</div>

				<!-- Sorting -->
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-2">
						Сортировка
					</label>
					<select wire:model.live="sortBy"
						class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
						<option value="created_at">Новые</option>
						<option value="rating">По рейтингу</option>
						<option value="review_count">По отзывам</option>
						<option value="star_rating">По звездам</option>
						<option value="name">По названию</option>
					</select>
				</div>

				<!-- Order -->
				<div>
					<label class="block text-sm font-semibold text-gray-700 mb-2">
						Порядок
					</label>
					<select wire:model.live="sortOrder"
						class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
						<option value="desc">По убыванию</option>
						<option value="asc">По возрастанию</option>
					</select>
				</div>
			</div>

			<!-- Checkbox Filters -->
			<div class="mt-4 pt-4 border-t">
				<div class="flex flex-wrap gap-4">
					<label class="flex items-center cursor-pointer">
						<input type="checkbox" wire:model.live="onlyHighRated"
							class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
						<span class="ml-2 text-sm text-gray-700">Только хорошие (4.5+)</span>
					</label>

					<label class="flex items-center cursor-pointer">
						<input type="checkbox" wire:model.live="onlyWithReviews"
							class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
						<span class="ml-2 text-sm text-gray-700">Только с отзывами</span>
					</label>

					<label class="flex items-center cursor-pointer">
						<input type="checkbox" wire:model.live="onlyCertified"
							class="w-4 h-4 text-green-600 rounded focus:ring-2 focus:ring-green-500">
						<span class="ml-2 text-sm text-gray-700">Только сертифицированные</span>
					</label>
				</div>
			</div>
		</div>

		<!-- Hotels Grid -->
		@if ($hotels->count() > 0)
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
				@foreach ($hotels as $hotel)
					<x-hotel-card :hotel="$hotel" show-rating="true" />
				@endforeach
			</div>

			<!-- Pagination -->
			<div class="mt-12">
				{{ $hotels->links() }}
			</div>
		@else
			<div class="text-center py-16">
				<svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor"
					viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
						d="M19 21l-7-5m0 0l-7 5m7-5v5m0-5L2.586 6.586A2 2 0 012 5.414V3a2 2 0 012-2h12a2 2 0 012 2v2.414a2 2 0 01-.586 1.414l-9 9z">
					</path>
				</svg>
				<h3 class="text-lg font-medium text-gray-900 mb-2">Отели не найдены</h3>
				<p class="text-gray-600">Попробуйте изменить параметры фильтра</p>
			</div>
		@endif
	</div>
</div>
