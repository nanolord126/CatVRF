<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300 h-full flex flex-col">
	<!-- Image Section -->
	<div class="relative h-48 bg-gray-200 overflow-hidden group">
		@if ($hotel->images && count($hotel->images) > 0)
			<img src="{{ $hotel->images[0] }}" alt="{{ $hotel->name }}"
				class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
		@else
			<div class="w-full h-full flex items-center justify-center text-gray-400">
				<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
						d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
					</path>
				</svg>
			</div>
		@endif

		<!-- Category Badge -->
		<div class="absolute top-2 left-2">
			<span class="px-3 py-1 bg-blue-500 text-white text-xs font-semibold rounded-full">
				{{ $hotel->getCategoryLabel() }}
			</span>
		</div>

		<!-- Star Rating Badge -->
		@if ($hotel->star_rating)
			<div class="absolute top-2 right-2">
				<span class="text-yellow-400 text-lg">
					{{ str_repeat('⭐', $hotel->star_rating) }}
				</span>
			</div>
		@endif

		<!-- Rosturism Badge -->
		@if ($hotel->certification_rosturism)
			<div class="absolute bottom-2 right-2">
				<span class="px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded">
					Ростуризм ✓
				</span>
			</div>
		@endif
	</div>

	<!-- Content Section -->
	<div class="flex-1 p-4 flex flex-col">
		<!-- Name and Rating -->
		<div class="mb-3">
			<h3 class="text-lg font-bold text-gray-900 line-clamp-2 mb-1">
				{{ $hotel->name }}
			</h3>

			@if ($showRating && $hotel->rating)
				<div class="flex items-center gap-2">
					<div class="flex items-center">
						<span class="text-sm font-semibold text-gray-900">{{ number_format($hotel->rating, 1) }}</span>
						<span class="ml-1 text-sm font-semibold text-gray-500">/5</span>
					</div>
					<span class="text-xs text-gray-500">({{ $hotel->review_count }} отзывов)</span>
				</div>
			@endif
		</div>

		<!-- Location -->
		@if ($hotel->address)
			<p class="text-sm text-gray-600 mb-3 flex items-start gap-2 line-clamp-2">
				<svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
					<path fill-rule="evenodd"
						d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
						clip-rule="evenodd" />
				</svg>
				<span>{{ $hotel->address }}</span>
			</p>
		@endif

		<!-- Amenities -->
		@if ($hotel->amenities && count($hotel->amenities) > 0)
			<div class="mb-3">
				<p class="text-xs text-gray-600 font-semibold mb-1">Основные удобства:</p>
				<div class="flex flex-wrap gap-1">
					@foreach (array_slice($hotel->amenities, 0, 3) as $amenity)
						<span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
							{{ $amenity }}
						</span>
					@endforeach
					@if (count($hotel->amenities) > 3)
						<span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
							+{{ count($hotel->amenities) - 3 }}
						</span>
					@endif
				</div>
			</div>
		@endif

		<!-- Room Types -->
		@if ($hotel->room_types && count($hotel->room_types) > 0 && !$compact)
			<div class="mb-3">
				<p class="text-xs text-gray-600 font-semibold mb-1">Типы номеров:</p>
				<ul class="text-xs text-gray-600 space-y-1">
					@foreach (array_slice($hotel->room_types, 0, 2) as $roomType)
						<li class="flex items-center gap-1">
							<span class="w-1 h-1 bg-gray-400 rounded-full"></span>
							{{ $roomType }}
						</li>
					@endforeach
					@if (count($hotel->room_types) > 2)
						<li class="text-gray-500 italic">
							+{{ count($hotel->room_types) - 2 }} еще
						</li>
					@endif
				</ul>
			</div>
		@endif

		<!-- Check-in/Check-out Times -->
		<div class="flex justify-between text-xs text-gray-500 mb-3 border-t pt-2">
			<div>
				<span class="font-semibold">Заезд:</span>
				{{ $hotel->check_in_time?->format('H:i') ?? '14:00' }}
			</div>
			<div>
				<span class="font-semibold">Выезд:</span>
				{{ $hotel->check_out_time?->format('H:i') ?? '11:00' }}
			</div>
		</div>

		<!-- Status -->
		<div class="flex items-center justify-between mt-auto pt-3 border-t">
			<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
				@if ($hotel->status === 'active')
					bg-green-100 text-green-800
				@else
					bg-red-100 text-red-800
				@endif">
				{{ $hotel->status === 'active' ? '✓ Доступен' : 'Недоступен' }}
			</span>
		</div>

		<!-- Action Button -->
		<a href="{{ route('hotel.show', $hotel->id) }}" class="mt-3 w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded transition-colors">
			Подробнее
		</a>
	</div>
</div>
