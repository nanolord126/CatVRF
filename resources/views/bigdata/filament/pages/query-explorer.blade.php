<x-filament-panels::page>
    <x-slot name="heading">
        Query Explorer
    </x-slot>

    <div class="space-y-6">
        <!-- Query Input -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">Execute SQL Query</h3>
            </div>
            <div class="p-4">
                <form wire:submit="executeQuery">
                    <textarea
                        wire:model="query"
                        class="w-full h-48 p-3 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="SELECT ... FROM ..."
                    ></textarea>

                    <div class="mt-4 flex items-center justify-between">
                        <div class="space-x-2">
                            @foreach($this->getExampleQueries() as $name => $example)
                                <button
                                    type="button"
                                    wire:click="loadExample('{{ $example }}')"
                                    class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded-md transition"
                                >
                                    {{ $name }}
                                </button>
                            @endforeach
                        </div>

                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                        >
                            <x-heroicon-o-play class="w-4 h-4" />
                            Execute Query
                        </button>
                    </div>
                </form>

                @if($error)
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-600">{{ $error }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Results -->
        @if(!empty($results))
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Results</h3>
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span>{{ $rowCount }} rows</span>
                        <span>{{ $executionTime }}ms</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach(array_keys($results[0]) as $column)
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ $column }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($results as $row)
                                <tr>
                                    @foreach($row as $value)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ is_null($value) ? 'NULL' : $value }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
