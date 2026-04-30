<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Shift Management</h1>
                <div class="flex space-x-2">
                    <button wire:click="refresh" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Refresh</button>
                    <button wire:click="openCreateModal" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">+ Create Shift</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Toggle -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div class="flex space-x-2">
            <button wire:click="setView('day')" 
                    class="{{ $view === 'day' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' }} px-4 py-2 rounded border">
                Day
            </button>
            <button wire:click="setView('week')" 
                    class="{{ $view === 'week' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' }} px-4 py-2 rounded border">
                Week
            </button>
            <button wire:click="setView('month')" 
                    class="{{ $view === 'month' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' }} px-4 py-2 rounded border">
                Month
            </button>
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
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Shift Schedule ({{ ucfirst($view) }} View)</h3>
                </div>
                <div class="px-6 py-4">
                    @if(isset($shifts['error']))
                        <p class="text-red-500">{{ $shifts['error'] }}</p>
                    @elseif(empty($shifts))
                        <p class="text-gray-500">No shifts scheduled</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($shifts as $shift)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $shift['employee_name'] ?? '' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $shift['start_date'] ?? '' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $shift['end_date'] ?? '' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {{ ucfirst($shift['shift_type'] ?? 'regular') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ match($shift['status'] ?? 'scheduled') {
                                                    'scheduled' => 'bg-green-100 text-green-800',
                                                    'in_progress' => 'bg-blue-100 text-blue-800',
                                                    'completed' => 'bg-gray-100 text-gray-800',
                                                    'cancelled' => 'bg-red-100 text-red-800',
                                                    default => 'bg-gray-100 text-gray-800',
                                                } }}">
                                                    {{ ucfirst($shift['status'] ?? 'scheduled') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button wire:click="openSubstituteModal({{ $shift['id'] }})" class="text-blue-600 hover:text-blue-900 mr-3">Find Substitute</button>
                                                <button wire:click="deleteShift({{ $shift['id'] }})" class="text-red-600 hover:text-red-900">Delete</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Create Shift Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900">Create Shift</h3>
                    <form wire:submit="createShift" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employee ID</label>
                            <input type="number" wire:model="createForm.employee_id" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date & Time</label>
                            <input type="datetime-local" wire:model="createForm.start_date" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date & Time</label>
                            <input type="datetime-local" wire:model="createForm.end_date" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Shift Type</label>
                            <select wire:model="createForm.shift_type" class="mt-1 block w-full px-3 py-2 border rounded">
                                <option value="regular">Regular</option>
                                <option value="night">Night</option>
                                <option value="overtime">Overtime</option>
                                <option value="on_call">On Call</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-2 mt-6">
                            <button type="button" wire:click="closeCreateModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Substitute Modal -->
    @if($showSubstituteModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900">Assign Substitute</h3>
                    <form wire:submit="assignSubstitute" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Substitute Employee ID</label>
                            <input type="number" wire:model="substituteForm.substitute_id" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div class="flex justify-end space-x-2 mt-6">
                            <button type="button" wire:click="closeSubstituteModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Assign</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
