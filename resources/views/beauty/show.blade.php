@extends('layouts.app')

@section('title', $salon->name . ' - CatVRF')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Salon Header -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="h-64 bg-gradient-to-r from-pink-400 to-purple-500"></div>
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $salon->name }}</h1>
                    <p class="text-gray-600 mt-2">{{ $salon->address }}</p>
                    <div class="flex items-center mt-2">
                        <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="ml-1 text-lg font-semibold">{{ $salon->rating }}</span>
                        <span class="text-gray-500 ml-2">({{ $salon->review_count }} reviews)</span>
                    </div>
                </div>
                <button class="bg-pink-600 text-white px-6 py-3 rounded-lg hover:bg-pink-700">Book Appointment</button>
            </div>
        </div>
    </div>

    <!-- Masters Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Our Masters</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($salon->masters as $master)
            <div class="text-center">
                <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-3"></div>
                <h3 class="font-semibold">{{ $master->name }}</h3>
                <p class="text-gray-600 text-sm">{{ $master->specialization }}</p>
                <p class="text-pink-600 font-semibold mt-2">{{ $master->price }} ₽/hour</p>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Services Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Services</h2>
        <div class="space-y-4">
            @foreach($salon->services_with_prices as $service)
            <div class="flex justify-between items-center p-4 border rounded-lg hover:bg-gray-50">
                <div>
                    <h3 class="font-semibold">{{ $service->name }}</h3>
                    <p class="text-gray-600 text-sm">{{ $service->description }}</p>
                    <p class="text-gray-500 text-sm mt-1">{{ $service->duration }} min</p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-gray-900">{{ $service->price }} ₽</p>
                    <button class="text-pink-600 hover:text-pink-700 text-sm">Book</button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Reviews</h2>
        <div class="space-y-4">
            @foreach($salon->reviews as $review)
            <div class="border-b pb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-semibold">{{ $review->user_name }}</h4>
                        <div class="flex text-yellow-500 mt-1">
                            @for($i = 1; $i <= 5; $i++)
                            @if($i <= $review->rating)
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            @else
                            <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            @endif
                            @endfor
                        </div>
                    </div>
                    <span class="text-gray-500 text-sm">{{ $review->created_at->diffForHumans() }}</span>
                </div>
                <p class="text-gray-700 mt-2">{{ $review->comment }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
