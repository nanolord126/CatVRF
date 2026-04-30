@extends('layouts.app')

@section('title', 'Beauty Salons - CatVRF')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Beauty Salons</h1>
        <div class="flex gap-4">
            <input type="text" placeholder="Search salons..." class="border rounded-lg px-4 py-2">
            <select class="border rounded-lg px-4 py-2">
                <option>All Services</option>
                <option>Haircut</option>
                <option>Manicure</option>
                <option>Facial</option>
                <option>Massage</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Salon Cards -->
        @foreach($salons as $salon)
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="h-48 bg-gradient-to-r from-pink-400 to-purple-500"></div>
            <div class="p-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold">{{ $salon->name }}</h3>
                        <p class="text-gray-600 text-sm">{{ $salon->address }}</p>
                    </div>
                    <div class="flex items-center text-yellow-500">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="ml-1">{{ $salon->rating }}</span>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    @foreach($salon->services as $service)
                    <span class="bg-pink-100 text-pink-800 text-xs px-2 py-1 rounded">{{ $service }}</span>
                    @endforeach
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <span class="text-lg font-bold text-gray-900">From {{ $salon->min_price }} ₽</span>
                    <a href="{{ route('beauty.show', $salon->id) }}" class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700">Book Now</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($salons->hasMorePages())
    <div class="mt-8 text-center">
        {{ $salons->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
