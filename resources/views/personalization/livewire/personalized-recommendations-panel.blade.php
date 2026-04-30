<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold">Personalized Recommendations</h3>
            <p class="text-sm text-gray-500">{{ $verticalLabel }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="refresh" class="p-2 text-gray-500 hover:text-gray-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
            <button wire:click="toggleViewMode" class="p-2 text-gray-500 hover:text-gray-700">
                @if($viewMode === 'grid')
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                @else
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                @endif
            </button>
        </div>
    </div>
    
    <!-- Stats -->
    @if(!$loading && $recommendations->count() > 0)
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-gray-900">{{ $recommendationCount }}</div>
                <div class="text-xs text-gray-500">Recommendations</div>
            </div>
            <div class="bg-green-50 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-green-700">{{ $highConfidenceCount }}</div>
                <div class="text-xs text-gray-500">High Confidence</div>
            </div>
            <div class="bg-blue-50 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-blue-700">{{ number_format($averageConfidence * 100, 0) }}%</div>
                <div class="text-xs text-gray-500">Avg Confidence</div>
            </div>
        </div>
    @endif
    
    @if($loading)
        <div class="flex items-center justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    @elseif($recommendations->count() > 0)
        @if($viewMode === 'grid')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($recommendations as $index => $rec)
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer" 
                         wire:click="showRecommendationDetails({{ $index }})">
                        <div class="flex items-start justify-between mb-2">
                            <h4 class="font-medium text-gray-900">{{ $rec->name }}</h4>
                            <span class="px-2 py-1 text-xs rounded-full {{ $this->getConfidenceColor($rec->confidence) }}">
                                {{ number_format($rec->confidence * 100, 0) }}%
                            </span>
                        </div>
                        <div class="mb-2">
                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-indigo-600" 
                                     :style="{ width: $this->getScorePercentage($rec->score) . '%' }">
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">{{ $rec->getPrimaryReason() }}</div>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button wire:click.stop="recordFeedback({{ $rec->id }}, 'click')" 
                                    class="text-xs text-indigo-600 hover:text-indigo-800">
                                View
                            </button>
                            <button wire:click.stop="recordFeedback({{ $rec->id }}, 'book')" 
                                    class="text-xs text-green-600 hover:text-green-800">
                                Book
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="space-y-3">
                @foreach($recommendations as $index => $rec)
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer flex items-center justify-between"
                         wire:click="showRecommendationDetails({{ $index }})">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h4 class="font-medium text-gray-900">{{ $rec->name }}</h4>
                                <span class="px-2 py-1 text-xs rounded-full {{ $this->getConfidenceColor($rec->confidence) }}">
                                    {{ number_format($rec->confidence * 100, 0) }}%
                                </span>
                            </div>
                            <div class="text-sm text-gray-500 mt-1">{{ $rec->getPrimaryReason() }}</div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <div class="text-sm font-medium text-indigo-600">{{ $this->getScorePercentage($rec->score) }}%</div>
                                <div class="text-xs text-gray-500">Score</div>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click.stop="recordFeedback({{ $rec->id }}, 'click')" 
                                        class="p-2 text-indigo-600 hover:text-indigo-800">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button wire:click.stop="recordFeedback({{ $rec->id }}, 'book')" 
                                        class="p-2 text-green-600 hover:text-green-800">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="text-center py-12 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-2">No recommendations available</p>
        </div>
    @endif
    
    <!-- Explanation Modal -->
    @if($showExplanation && $selectedRecommendation)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click="hideExplanation">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4" wire:click.stop>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">{{ $selectedRecommendation->name }}</h3>
                        <button wire:click="hideExplanation" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm text-gray-500">Score</div>
                            <div class="text-2xl font-bold text-indigo-600">{{ number_format($selectedRecommendation->score * 100, 1) }}%</div>
                        </div>
                        
                        <div>
                            <div class="text-sm text-gray-500">Confidence</div>
                            <div class="text-lg font-semibold">{{ number_format($selectedRecommendation->confidence * 100, 0) }}%</div>
                        </div>
                        
                        <div>
                            <div class="text-sm text-gray-500 mb-2">Why recommended</div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <div class="font-medium text-gray-900">{{ ucfirst($selectedRecommendation->getPrimaryReason()) }}</div>
                            </div>
                        </div>
                        
                        @if(!empty($selectedRecommendation->explanation['factors']))
                        <div>
                            <div class="text-sm text-gray-500 mb-2">Factors</div>
                            <div class="space-y-2">
                                @foreach($selectedRecommendation->explanation['factors'] as $factor)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-700">{{ $factor }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    
                    <div class="mt-6 flex gap-2">
                        <button wire:click="recordFeedback({{ $selectedRecommendation->id }}, 'book')" 
                                class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Book Now
                        </button>
                        <button wire:click="hideExplanation" 
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    @session('error')
        <div class="mt-4 p-4 bg-red-100 text-red-700 rounded-md">
            {{ session('error') }}
        </div>
    @endsession
</div>
