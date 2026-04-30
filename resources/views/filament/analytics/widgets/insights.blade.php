<div class="space-y-4">
    @forelse($insights as $insight)
        <div class="rounded-lg border p-4 {{ $insight['severity'] === 'critical' ? 'border-red-500 bg-red-50' : ($insight['severity'] === 'warning' ? 'border-yellow-500 bg-yellow-50' : ($insight['severity'] === 'opportunity' ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-white')) }}">
            <div class="flex items-start justify-between">
                <div>
                    <h4 class="font-semibold {{ $insight['severity'] === 'critical' ? 'text-red-800' : ($insight['severity'] === 'warning' ? 'text-yellow-800' : ($insight['severity'] === 'opportunity' ? 'text-green-800' : 'text-gray-800')) }}">
                        {{ $insight['title'] }}
                    </h4>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ $insight['message'] }}
                    </p>
                </div>
                @if($insight['severity'] === 'critical')
                    <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">Critical</span>
                @elseif($insight['severity'] === 'warning')
                    <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">Warning</span>
                @elseif($insight['severity'] === 'opportunity')
                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Opportunity</span>
                @else
                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800">Info</span>
                @endif
            </div>

            @if(!empty($insight['recommendations']))
                <div class="mt-3">
                    <p class="text-xs font-semibold text-gray-700 uppercase">Recommendations:</p>
                    <ul class="mt-1 list-inside list-disc text-sm text-gray-600">
                        @foreach($insight['recommendations'] as $recommendation)
                            <li>{{ $recommendation }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-lg border border-gray-200 bg-white p-4 text-center text-gray-500">
            <p>No insights available at this time.</p>
        </div>
    @endforelse
</div>
