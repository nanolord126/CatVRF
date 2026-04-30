<div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Housekeeping Board</h2>
            <div class="flex items-center space-x-2">
                <button wire:click="previousDay" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                    ←
                </button>
                <span class="text-lg font-medium">{{ $date->format('M j, Y') }}</span>
                <button wire:click="nextDay" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                    →
                </button>
                <button wire:click="today" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Today
                </button>
            </div>
        </div>

        @if(count($overdueTasks) > 0)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <h3 class="text-lg font-semibold text-red-800 mb-2">Overdue Tasks ({{ count($overdueTasks) }})</h3>
                <div class="space-y-2">
                    @foreach($overdueTasks as $task)
                        <div class="flex items-center justify-between bg-white rounded p-2">
                            <div>
                                <span class="font-medium">{{ $task->room->room_number }}</span>
                                <span class="text-gray-600 ml-2">{{ $task->taskType }}</span>
                                <span class="text-red-600 text-sm ml-2">Due: {{ $task->scheduledFor?->format('H:i') }}</span>
                            </div>
                            <button wire:click="startTask({{ $task->id }})" class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                                Start
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-4 gap-4">
            <!-- Pending Tasks -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-4 text-gray-700">Pending ({{ count($boardData['pending']) }})</h3>
                <div class="space-y-2">
                    @foreach($boardData['pending'] as $task)
                        <div class="bg-white rounded p-3 border">
                            <div class="font-medium">{{ $task->room->room_number }}</div>
                            <div class="text-sm text-gray-600">{{ $task->taskType }}</div>
                            <div class="text-sm text-gray-500">{{ $task->scheduledFor?->format('H:i') }}</div>
                            <div class="mt-2">
                                <button wire:click="startTask({{ $task->id }})" class="px-2 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600">
                                    Start
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- In Progress Tasks -->
            <div class="bg-blue-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-4 text-blue-700">In Progress ({{ count($boardData['in_progress']) }})</h3>
                <div class="space-y-2">
                    @foreach($boardData['in_progress'] as $task)
                        <div class="bg-white rounded p-3 border border-blue-200">
                            <div class="font-medium">{{ $task->room->room_number }}</div>
                            <div class="text-sm text-gray-600">{{ $task->taskType }}</div>
                            <div class="text-sm text-blue-600">Started: {{ $task->startedAt?->format('H:i') }}</div>
                            <div class="mt-2">
                                <button wire:click="completeTask({{ $task->id }})" class="px-2 py-1 bg-green-500 text-white rounded text-xs hover:bg-green-600">
                                    Complete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Completed Tasks -->
            <div class="bg-green-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-4 text-green-700">Completed ({{ count($boardData['completed']) }})</h3>
                <div class="space-y-2">
                    @foreach($boardData['completed'] as $task)
                        <div class="bg-white rounded p-3 border border-green-200 opacity-75">
                            <div class="font-medium">{{ $task->room->room_number }}</div>
                            <div class="text-sm text-gray-600">{{ $task->taskType }}</div>
                            <div class="text-sm text-green-600">Completed: {{ $task->completedAt?->format('H:i') }}</div>
                            <div class="text-xs text-gray-500">{{ $task->actualMinutes }} min</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-white rounded-lg p-4 border">
                <h3 class="text-lg font-semibold mb-4">Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Tasks</span>
                        <span class="font-medium">{{ count($boardData['pending']) + count($boardData['in_progress']) + count($boardData['completed']) + count($boardData['overdue']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Pending</span>
                        <span class="font-medium text-yellow-600">{{ count($boardData['pending']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">In Progress</span>
                        <span class="font-medium text-blue-600">{{ count($boardData['in_progress']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Completed</span>
                        <span class="font-medium text-green-600">{{ count($boardData['completed']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Overdue</span>
                        <span class="font-medium text-red-600">{{ count($boardData['overdue']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
