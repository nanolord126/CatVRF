<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Manager Dashboard</h1>
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
                <button wire:click="setActiveTab('team')"
                        class="{{ $activeTab === 'team' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Team Dynamics
                </button>
                <button wire:click="setActiveTab('schedule')"
                        class="{{ $activeTab === 'schedule' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Schedule
                </button>
                <button wire:click="setActiveTab('forecast')"
                        class="{{ $activeTab === 'forecast' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Hiring Forecast
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
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Team Size</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $teamDynamics['team_size'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Avg Performance</div>
                        <div class="mt-2 text-3xl font-bold text-blue-600">{{ $teamDynamics['avg_performance'] ?? 0 }}%</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">High Burnout Risk</div>
                        <div class="mt-2 text-3xl font-bold text-red-600">{{ $teamDynamics['high_burnout'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Top Performer</div>
                        <div class="mt-2 text-xl font-bold text-green-600">{{ $teamDynamics['top_performer'] ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="mt-6 bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Leaderboard</h3>
                    @if(empty($leaderboard))
                        <p class="text-gray-500">No leaderboard data</p>
                    @else
                        <ul class="space-y-3">
                            @foreach($leaderboard as $rank => $employee)
                                <li class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                    <div class="flex items-center">
                                        <span class="text-lg font-bold text-blue-600 mr-3">#{{ $rank + 1 }}</span>
                                        <span class="font-medium">{{ $employee['name'] ?? '' }}</span>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ $employee['points'] ?? 0 }} pts</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <!-- Team Dynamics Tab -->
            @if($activeTab === 'team')
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Team Dynamics</h3>
                        <button wire:click="analyzeTeam" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Analyze</button>
                    </div>
                    <pre class="bg-gray-100 p-4 rounded overflow-auto">{{ json_encode($teamDynamics, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            <!-- Schedule Tab -->
            @if($activeTab === 'schedule')
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Schedule Optimization</h3>
                        <button wire:click="optimizeSchedule" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Optimize</button>
                    </div>
                    <pre class="bg-gray-100 p-4 rounded overflow-auto">{{ json_encode($scheduleOptimization, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            <!-- Hiring Forecast Tab -->
            @if($activeTab === 'forecast')
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Hiring Forecast ({{ $horizonMonths }} months)</h3>
                        <div class="flex items-center space-x-2">
                            <select wire:model="horizonMonths" class="px-3 py-2 border rounded">
                                <option value="3">3 months</option>
                                <option value="6">6 months</option>
                                <option value="12">12 months</option>
                            </select>
                            <button wire:click="generateForecast" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Generate</button>
                        </div>
                    </div>
                    <pre class="bg-gray-100 p-4 rounded overflow-auto">{{ json_encode($hiringForecast, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        @endif
    </div>
</div>
