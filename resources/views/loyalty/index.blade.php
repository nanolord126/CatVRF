@extends('layouts.user-cabinet')

@section('title', 'Loyalty Program - CatVRF')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Loyalty Tier Card -->
    <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg shadow-lg p-8 mb-8 text-white">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-xl font-semibold mb-2">Loyalty Tier</h2>
                <div class="flex items-baseline">
                    <span class="text-4xl font-bold">{{ $user->loyalty_tier }}</span>
                </div>
                <p class="mt-2 text-amber-100">{{ $user->loyalty_tier_description }}</p>
            </div>
            <div class="text-right">
                <p class="text-amber-100">Points Balance</p>
                <div class="flex items-baseline justify-end">
                    <span class="text-4xl font-bold">{{ number_format($user->loyalty_points) }}</span>
                    <span class="text-xl ml-2">pts</span>
                </div>
            </div>
        </div>
        
        <!-- Progress to next tier -->
        <div class="mt-6">
            <div class="flex justify-between text-sm mb-2">
                <span>Progress to {{ $nextTier }}</span>
                <span>{{ $pointsToNextTier }} pts needed</span>
            </div>
            <div class="w-full bg-amber-700 rounded-full h-3">
                <div class="bg-white h-3 rounded-full" style="width: {{ $tierProgress }}%"></div>
            </div>
        </div>
    </div>

    <!-- Tier Benefits -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Your Benefits</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="border rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <svg class="w-6 h-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="ml-2 font-semibold">{{ $user->points_multiplier }}x Points</span>
                </div>
                <p class="text-gray-600 text-sm">Earn {{ $user->points_multiplier }} points for every 1 ₽ spent</p>
            </div>
            <div class="border rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="ml-2 font-semibold">{{ $user->priority_support }}</span>
                </div>
                <p class="text-gray-600 text-sm">Priority customer support</p>
            </div>
            <div class="border rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="ml-2 font-semibold">{{ $user->discount_percent }}% Discount</span>
                </div>
                <p class="text-gray-600 text-sm">Exclusive discounts on services</p>
            </div>
        </div>
    </div>

    <!-- Points History -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Points History</h2>
            <button class="text-blue-600 hover:text-blue-800">View All</button>
        </div>
        <div class="space-y-4">
            @foreach($recentPoints as $points)
            <div class="flex justify-between items-center p-4 border rounded-lg hover:bg-gray-50">
                <div class="flex items-center">
                    <div class="w-10 h-10 @if($points->type === 'earned') bg-green-100 @elseif($points->type === 'redeemed') bg-blue-100 @else bg-gray-100 @endif rounded-full flex items-center justify-center">
                        @if($points->type === 'earned')
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        @elseif($points->type === 'redeemed')
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                        @else
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        @endif
                    </div>
                    <div class="ml-4">
                        <h4 class="font-semibold text-gray-900">{{ $points->description }}</h4>
                        <p class="text-gray-600 text-sm">{{ $points->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold @if($points->type === 'earned') text-green-600 @elseif($points->type === 'redeemed') text-blue-600 @else text-gray-600 @endif">
                        @if($points->type === 'earned')+@endif{{ number_format($points->amount) }} pts
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Rewards Catalog -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Rewards Catalog</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($rewards as $reward)
            <div class="border rounded-lg p-4 hover:shadow-lg transition-shadow">
                <div class="h-32 bg-gradient-to-r from-purple-400 to-pink-500 rounded mb-4"></div>
                <h3 class="font-semibold text-gray-900">{{ $reward->name }}</h3>
                <p class="text-gray-600 text-sm mt-1">{{ $reward->description }}</p>
                <div class="mt-4 flex justify-between items-center">
                    <span class="font-bold text-amber-600">{{ number_format($reward->points_cost) }} pts</span>
                    @if($user->loyalty_points >= $reward->points_cost)
                    <button class="bg-amber-500 text-white px-4 py-2 rounded-lg hover:bg-amber-600">Redeem</button>
                    @else
                    <button class="bg-gray-300 text-gray-500 px-4 py-2 rounded-lg cursor-not-allowed">Not enough</button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
