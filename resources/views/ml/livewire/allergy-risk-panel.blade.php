<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Allergy Risk Assessment</h3>
        <button wire:click="loadRiskPrediction" class="px-3 py-1 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
            {{ $loading ? 'Loading...' : 'Check Risk' }}
        </button>
    </div>
    
    @if($loading)
        <div class="flex items-center justify-center py-8">
            <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    @elseif($riskPrediction)
        <div class="space-y-4">
            <!-- Risk Level Indicator -->
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Risk Level</span>
                        <span class="text-sm font-bold" :class="'text-' . $this->riskColor">
                            {{ ucfirst($riskPrediction->riskLevel->value) }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-300" 
                             :class="'bg-' . $this->riskColor"
                             :style="{ width: ($riskPrediction->probability * 100) + '%' }">
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ number_format($riskPrediction->probability * 100, 1) }}% probability
                    </div>
                </div>
                
                <div class="text-center">
                    <x-dynamic-component :component="'heroicon-' . $this->riskIcon" 
                                    class="h-12 w-12"
                                    :class="'text-' . $this->riskColor" />
                </div>
            </div>
            
            <!-- Confidence -->
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Confidence</span>
                    <span class="text-sm font-medium">{{ number_format($riskPrediction->confidence * 100, 0) }}%</span>
                </div>
            </div>
            
            <!-- Cross Allergies -->
            @if(!empty($riskPrediction->crossAllergies))
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-yellow-800 mb-2">Potential Cross-Allergies Detected</h4>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        @foreach($riskPrediction->crossAllergies as $crossAllergy)
                            <li>• {{ $crossAllergy }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <!-- Safe Alternatives -->
            @if(!empty($riskPrediction->safeAlternatives))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-green-800 mb-2">Safe Alternatives Available</h4>
                    <ul class="text-sm text-green-700 space-y-1">
                        @foreach($riskPrediction->safeAlternatives as $alt)
                            <li>• {{ $alt['name'] }} ({{ $alt['type'] }})</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <!-- Toggle Details -->
            <button wire:click="$toggle('showDetails')" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                {{ $showDetails ? 'Hide' : 'Show' }} Details
            </button>
            
            @if($showDetails)
                <div class="bg-gray-50 rounded-lg p-4 mt-2">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Risk Details</h4>
                    <div class="text-sm text-gray-600 space-y-1">
                        <div><strong>Probability:</strong> {{ number_format($riskPrediction->probability * 100, 2) }}%</div>
                        <div><strong>Confidence:</strong> {{ number_format($riskPrediction->confidence * 100, 2) }}%</div>
                        <div><strong>Risk Level:</strong> {{ ucfirst($riskPrediction->riskLevel->value) }}</div>
                        @if(!empty($riskPrediction->features))
                            <div><strong>Features:</strong> {{ count($riskPrediction->features) }} factors analyzed</div>
                        @endif
                    </div>
                </div>
            @endif
            
            <!-- Block Warning -->
            @if($this->shouldBlock)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="h-6 w-6 text-red-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h4 class="text-sm font-semibold text-red-800">Booking Blocked</h4>
                            <p class="text-sm text-red-700 mt-1">Critical allergy risk detected. This service is not recommended.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <p>Click "Check Risk" to assess allergy risk</p>
        </div>
    @endif
    
    @session('error')
        <div class="mt-4 p-4 bg-red-100 text-red-700 rounded-md">
            {{ session('error') }}
        </div>
    @endsession
</div>
