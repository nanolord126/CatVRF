@extends('layouts.user-cabinet')

@section('title', 'My Wallet - CatVRF')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Wallet Balance Card -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-8 mb-8 text-white">
        <h2 class="text-xl font-semibold mb-2">Wallet Balance</h2>
        <div class="flex items-baseline">
            <span class="text-5xl font-bold">{{ number_format($wallet->balance / 100, 2) }}</span>
            <span class="text-2xl ml-2">₽</span>
        </div>
        <div class="mt-4 flex gap-4">
            <button class="bg-white text-blue-600 px-6 py-2 rounded-lg font-semibold hover:bg-gray-100">Top Up</button>
            <button class="bg-blue-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-400">Transfer</button>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-900">Add Money</h3>
                    <p class="text-gray-600 text-sm">Top up your wallet</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-900">Transfer</h3>
                    <p class="text-gray-600 text-sm">Send to another user</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-900">Statement</h3>
                    <p class="text-gray-600 text-sm">View transaction history</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Recent Transactions</h2>
            <a href="#" class="text-blue-600 hover:text-blue-800">View All</a>
        </div>
        <div class="space-y-4">
            @foreach($recentTransactions as $transaction)
            <div class="flex justify-between items-center p-4 border rounded-lg hover:bg-gray-50">
                <div class="flex items-center">
                    <div class="w-10 h-10 @if($transaction->type === 'deposit') bg-green-100 @elseif($transaction->type === 'withdrawal') bg-red-100 @else bg-blue-100 @endif rounded-full flex items-center justify-center">
                        @if($transaction->type === 'deposit')
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        @elseif($transaction->type === 'withdrawal')
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                        @else
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        @endif
                    </div>
                    <div class="ml-4">
                        <h4 class="font-semibold text-gray-900">{{ $transaction->description }}</h4>
                        <p class="text-gray-600 text-sm">{{ $transaction->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-semibold @if($transaction->type === 'deposit') text-green-600 @elseif($transaction->type === 'withdrawal') text-red-600 @else text-gray-900 @endif">
                        @if($transaction->type === 'deposit')+@endif{{ number_format($transaction->amount / 100, 2) }} ₽
                    </p>
                    <span class="text-xs text-gray-500">Balance: {{ number_format($transaction->balance_after / 100, 2) }} ₽</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
