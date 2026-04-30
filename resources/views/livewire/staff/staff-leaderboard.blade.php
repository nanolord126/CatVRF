<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Leaderboard</h1>
                <div class="flex space-x-2">
                    <button wire:click="refresh" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Refresh</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div class="flex flex-wrap gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                <div class="flex space-x-2">
                    @foreach($periods as $key => $label)
                        <button wire:click="setPeriod('{{ $key }}')"
                                class="{{ $period === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' }} px-3 py-2 rounded border text-sm">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <div class="flex space-x-2">
                    @foreach($categories as $key => $label)
                        <button wire:click="setCategory('{{ $key }}')"
                                class="{{ $category === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' }} px-3 py-2 rounded border text-sm">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if($loading)
            <div class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-4 text-gray-500">Loading...</p>
            </div>
        @else
            <!-- User Rank -->
            @if(!empty($userRank))
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg shadow-lg p-6 mb-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Your Rank</p>
                            <p class="text-4xl font-bold">#{{ $userRank['rank'] ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm opacity-90">Points</p>
                            <p class="text-3xl font-bold">{{ $userRank['points'] ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-sm opacity-90">Level</p>
                            <p class="text-3xl font-bold">{{ $userRank['level'] ?? 1 }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Leaderboard -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Top Performers</h3>
                </div>
                <div class="px-6 py-4">
                    @if(isset($leaderboard['error']))
                        <p class="text-red-500">{{ $leaderboard['error'] }}</p>
                    @elseif(empty($leaderboard))
                        <p class="text-gray-500">No leaderboard data available</p>
                    @else
                        <div class="space-y-4">
                            @foreach($leaderboard as $index => $employee)
                                <div class="flex items-center justify-between p-4 {{ $index < 3 ? 'bg-gradient-to-r from-yellow-50 to-orange-50' : 'bg-gray-50' }} rounded">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 flex items-center justify-center rounded-full {{ $index === 0 ? 'bg-yellow-400' : ($index === 1 ? 'bg-gray-300' : ($index === 2 ? 'bg-orange-400' : 'bg-gray-200')) }} text-white font-bold mr-4">
                                            {{ $index + 1 }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $employee['name'] ?? '' }}</p>
                                            <p class="text-sm text-gray-500">{{ $employee['department'] ?? '' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-right">
                                            <p class="text-lg font-bold text-blue-600">{{ $employee['points'] ?? 0 }}</p>
                                            <p class="text-xs text-gray-500">points</p>
                                        </div>
                                        <button wire:click="viewEmployeeProfile({{ $employee['id'] }})" class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200">View</button>
                                        <button wire:click="sendGratitude({{ $employee['id'] }})" class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded hover:bg-green-200">👍 Thank</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
