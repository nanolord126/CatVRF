@extends('layouts.app')

@section('content')
<main class="bg-gray-50 py-12">
	<div class="max-w-4xl mx-auto px-4">
		<!-- Back Link -->
		<a href="{{ route('hotels.catalog') }}"
			class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 mb-8 font-semibold">
			<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
			</svg>
			Вернуться в каталог
		</a>

		<div class="bg-white rounded-lg shadow-lg overflow-hidden">
			<!-- Main Image Gallery -->
			<div class="relative">
				@if ($hotel->images && count($hotel->images) > 0)
					<div class="grid grid-cols-4 gap-2 p-4 bg-black">
						<!-- Main image -->
						<div class="col-span-3">
							<img id="main-image" src="{{ $hotel->images[0] }}" alt="{{ $hotel->name }}"
								class="w-full h-96 object-cover rounded cursor-pointer hover:opacity-90">
						</div>

						<!-- Thumbnails -->
						<div class="col-span-1 space-y-2">
							@foreach ($hotel->images as $index => $image)
								<img src="{{ $image }}" alt="Gallery {{ $index + 1 }}"
									class="w-full h-20 object-cover rounded cursor-pointer hover:opacity-80 transition"
									onclick="document.getElementById('main-image').src='{{ $image }}'">
							@endforeach
						</div>
					</div>
				@else
					<div class="w-full h-96 bg-gray-300 flex items-center justify-center">
						<span class="text-gray-500">Изображения отсутствуют</span>
					</div>
				@endif
			</div>

			<!-- Header Section -->
			<div class="p-8">
				<div class="flex justify-between items-start mb-4">
					<div>
						<h1 class="text-4xl font-bold text-gray-900 mb-2">{{ $hotel->name }}</h1>

						<div class="flex items-center gap-4 mb-4">
							<!-- Star Rating -->
							@if ($hotel->star_rating)
								<div class="flex items-center gap-2">
									<span class="text-2xl">{{ str_repeat('⭐', $hotel->star_rating) }}</span>
									<span class="text-gray-600 font-semibold">{{ $hotel->star_rating }} звезд</span>
								</div>
							@endif

							<!-- Category Badge -->
							<span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full font-semibold">
								{{ $hotel->getCategoryLabel() }}
							</span>

							<!-- Rosturism Badge -->
							@if ($hotel->certification_rosturism)
								<span class="px-4 py-2 bg-green-100 text-green-800 rounded-full font-semibold flex items-center gap-1">
									✓ Ростуризм
								</span>
							@endif
						</div>

						<!-- Rating and Reviews -->
						@if ($hotel->rating)
							<div class="flex items-center gap-4">
								<div>
									<span class="text-3xl font-bold text-gray-900">{{ number_format($hotel->rating, 1) }}</span>
									<span class="text-gray-600">/5</span>
								</div>
								<p class="text-gray-600">{{ $hotel->review_count }} отзывов от гостей</p>
							</div>
						@endif
					</div>

					<!-- Contact Section -->
					<div class="text-right">
						@if ($hotel->phone)
							<p class="text-lg font-semibold text-gray-900 mb-2">
								<a href="tel:{{ $hotel->phone }}" class="text-blue-600 hover:text-blue-800">
									{{ $hotel->phone }}
								</a>
							</p>
						@endif

						@if ($hotel->email)
							<p class="text-gray-600 mb-4">
								<a href="mailto:{{ $hotel->email }}" class="text-blue-600 hover:text-blue-800">
									{{ $hotel->email }}
								</a>
							</p>
						@endif

						@if ($hotel->status === 'active')
							<span class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded font-semibold">
								✓ Доступен для бронирования
							</span>
						@else
							<span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded font-semibold">
								Недоступен
							</span>
						@endif
					</div>
				</div>

				<hr class="my-6">

				<!-- Description -->
				@if ($hotel->description)
					<div class="mb-8">
						<h2 class="text-2xl font-bold text-gray-900 mb-4">О гостинице</h2>
						<p class="text-gray-700 text-lg leading-relaxed">{{ $hotel->description }}</p>
					</div>
				@endif

				<!-- Location -->
				<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
					<div>
						<h3 class="text-xl font-bold text-gray-900 mb-4">Местоположение</h3>
						<p class="text-gray-700 text-lg mb-4">{{ $hotel->address }}</p>

						@if ($hotel->geo_lat && $hotel->geo_lng)
							<div class="w-full h-64 bg-gray-300 rounded-lg overflow-hidden">
								<iframe width="100%" height="100%"
									src="https://www.openstreetmap.org/export/embed.html?bbox={{ $hotel->geo_lng - 0.01 }},{{ $hotel->geo_lat - 0.01 }},{{ $hotel->geo_lng + 0.01 }},{{ $hotel->geo_lat + 0.01 }}&layer=mapnik&marker={{ $hotel->geo_lat }},{{ $hotel->geo_lng }}"
									style="border: 0;"></iframe>
							</div>
						@endif
					</div>

					<!-- Check-in/out Times -->
					<div>
						<h3 class="text-xl font-bold text-gray-900 mb-4">Информация о размещении</h3>

						<div class="space-y-4">
							<div class="bg-gray-50 p-4 rounded-lg">
								<p class="text-sm text-gray-600 mb-1">Время заезда</p>
								<p class="text-lg font-semibold text-gray-900">
									{{ $hotel->check_in_time?->format('H:i') ?? '14:00' }}
								</p>
							</div>

							<div class="bg-gray-50 p-4 rounded-lg">
								<p class="text-sm text-gray-600 mb-1">Время выезда</p>
								<p class="text-lg font-semibold text-gray-900">
									{{ $hotel->check_out_time?->format('H:i') ?? '11:00' }}
								</p>
							</div>

							@if ($hotel->registration_number)
								<div class="bg-blue-50 p-4 rounded-lg">
									<p class="text-sm text-gray-600 mb-1">Регистрационный номер</p>
									<p class="text-lg font-semibold text-blue-900">{{ $hotel->registration_number }}</p>
								</div>
							@endif
						</div>
					</div>
				</div>

				<!-- Room Types -->
				@if ($hotel->room_types && count($hotel->room_types) > 0)
					<div class="mb-8">
						<h3 class="text-xl font-bold text-gray-900 mb-4">Типы номеров</h3>
						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							@foreach ($hotel->room_types as $roomType)
								<div class="border-l-4 border-blue-600 pl-4 py-2">
									<p class="text-gray-700 font-semibold">{{ $roomType }}</p>
								</div>
							@endforeach
						</div>
					</div>
				@endif

				<!-- Amenities -->
				@if ($hotel->amenities && count($hotel->amenities) > 0)
					<div class="mb-8">
						<h3 class="text-xl font-bold text-gray-900 mb-4">Удобства и услуги</h3>
						<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
							@foreach ($hotel->amenities as $amenity)
								<div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg">
									<svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor"
										viewBox="0 0 20 20">
										<path fill-rule="evenodd"
											d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
											clip-rule="evenodd" />
									</svg>
									<span class="text-gray-700 font-medium">{{ $amenity }}</span>
								</div>
							@endforeach
						</div>
					</div>
				@endif

				<!-- Policies -->
				@if ($hotel->policies)
					<div class="mb-8">
						<h3 class="text-xl font-bold text-gray-900 mb-4">Политика отеля</h3>
						<p class="text-gray-700 text-lg leading-relaxed p-4 bg-gray-50 rounded-lg">
							{{ $hotel->policies }}
						</p>
					</div>
				@endif

				<!-- CTA Section -->
				<div class="mt-8 p-6 bg-blue-50 rounded-lg text-center">
					<h3 class="text-2xl font-bold text-gray-900 mb-2">Готовы к бронированию?</h3>
					<p class="text-gray-600 mb-4">Свяжитесь с отелем для получения информации о доступности и ценах</p>
					<div class="flex flex-wrap justify-center gap-4">
						@if ($hotel->phone)
							<a href="tel:{{ $hotel->phone }}"
								class="px-6 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
								Позвонить
							</a>
						@endif

						@if ($hotel->email)
							<a href="mailto:{{ $hotel->email }}"
								class="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition">
								Написать письмо
							</a>
						@endif
					</div>
				</div>
			</div>
		</div>

		<!-- Related Hotels -->
		<div class="mt-16">
			<h2 class="text-3xl font-bold text-gray-900 mb-8">Похожие отели</h2>
			<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
				@php
					$relatedHotels = \App\Models\Tenants\Hotel::query()
						->where('category', $hotel->category)
						->where('id', '!=', $hotel->id)
						->where('status', 'active')
						->limit(3)
						->get();
				@endphp

				@forelse ($relatedHotels as $relatedHotel)
					<x-hotel-card :hotel="$relatedHotel" show-rating="true" />
				@empty
					<p class="text-gray-600">Похожие отели не найдены</p>
				@endforelse
			</div>
		</div>
	</div>
</main>
@endsection
