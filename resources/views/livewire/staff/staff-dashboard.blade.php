<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">My Dashboard</h1>
                <div class="flex space-x-2">
                    <button wire:click="refresh" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Refresh</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="setActiveTab('overview')"
                        class="{{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Overview
                </button>
                <button wire:click="setActiveTab('performance')"
                        class="{{ $activeTab === 'performance' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Performance
                </button>
                <button wire:click="setActiveTab('achievements')"
                        class="{{ $activeTab === 'achievements' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Achievements
                </button>
                <button wire:click="setActiveTab('wellness')"
                        class="{{ $activeTab === 'wellness' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Wellness
                </button>
            </nav>
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
            <!-- Overview Tab -->
            @if($activeTab === 'overview')
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-medium text-gray-500">Performance Score</h3>
                        <div class="mt-2 text-3xl font-bold text-blue-600">{{ $performance['score'] ?? 'N/A' }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-medium text-gray-500">Burnout Risk</h3>
                        <div class="mt-2 text-3xl font-bold {{ ($burnout['risk_level'] ?? 'low') === 'high' ? 'text-red-600' : 'text-green-600' }}">
                            {{ ucfirst($burnout['risk_level'] ?? 'Low') }}
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-medium text-gray-500">Wellness Score</h3>
                        <div class="mt-2 text-3xl font-bold text-purple-600">{{ $wellness['score'] ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="mt-6 flex space-x-4">
                    <button wire:click="analyzePerformance" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Analyze Performance</button>
                    <button wire:click="predictBurnout" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">Predict Burnout</button>
                </div>
            @endif

            <!-- Performance Tab -->
            @if($activeTab === 'performance')
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Analysis</h3>
                    <pre class="bg-gray-100 p-4 rounded overflow-auto">{{ json_encode($performance, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            <!-- Achievements Tab -->
            @if($activeTab === 'achievements')
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Achievements</h3>
                    @if(empty($achievements))
                        <p class="text-gray-500">No achievements yet</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($achievements as $achievement)
                                <div class="p-4 border rounded">
                                    <div class="text-2xl">{{ $achievement['icon'] ?? '🏆' }}</div>
                                    <div class="mt-2 font-medium">{{ $achievement['name'] ?? '' }}</div>
                                    <div class="text-sm text-gray-500">{{ $achievement['description'] ?? '' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <!-- Wellness Tab -->
            @if($activeTab === 'wellness')
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Wellness Metrics</h3>
                    <pre class="bg-gray-100 p-4 rounded overflow-auto">{{ json_encode($wellness, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        @endif
    </div>
</div>
