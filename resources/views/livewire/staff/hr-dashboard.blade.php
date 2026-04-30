<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">HR Dashboard</h1>
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
                <button wire:click="setActiveTab('leaves')"
                        class="{{ $activeTab === 'leaves' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Leave Requests
                </button>
                <button wire:click="setActiveTab('shifts')"
                        class="{{ $activeTab === 'shifts' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Shift Schedule
                </button>
                <button wire:click="setActiveTab('hiring')"
                        class="{{ $activeTab === 'hiring' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Hiring Pipeline
                </button>
                <button wire:click="setActiveTab('wellness')"
                        class="{{ $activeTab === 'wellness' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Wellness Overview
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
            <!-- Leave Requests Tab -->
            @if($activeTab === 'leaves')
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Leave Requests</h3>
                            <button wire:click="openLeaveModal" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">+ Request Leave</button>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-4">
                            @if(isset($leaveRequests['pending']) && count($leaveRequests['pending']) > 0)
                                @foreach($leaveRequests['pending'] as $leave)
                                    <div class="flex items-center justify-between p-4 bg-yellow-50 rounded">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $leave['employee_name'] ?? '' }}</p>
                                            <p class="text-sm text-gray-500">{{ $leave['type'] ?? '' }}: {{ $leave['start_date'] ?? '' }} - {{ $leave['end_date'] ?? '' }}</p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button wire:click="approveLeave({{ $leave['id'] }})" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">Approve</button>
                                            <button wire:click="rejectLeave({{ $leave['id'] }})" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">Reject</button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-gray-500">No pending leave requests</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Shift Schedule Tab -->
            @if($activeTab === 'shifts')
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Shift Schedule</h3>
                            <button wire:click="openShiftModal" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">+ Create Shift</button>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-gray-500">Shift calendar view coming soon...</p>
                    </div>
                </div>
            @endif

            <!-- Hiring Pipeline Tab -->
            @if($activeTab === 'hiring')
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Candidates</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $hiringPipeline['candidates'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Interviews</div>
                        <div class="mt-2 text-3xl font-bold text-blue-600">{{ $hiringPipeline['interviews'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Offers</div>
                        <div class="mt-2 text-3xl font-bold text-yellow-600">{{ $hiringPipeline['offers'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Hired</div>
                        <div class="mt-2 text-3xl font-bold text-green-600">{{ $hiringPipeline['hired'] ?? 0 }}</div>
                    </div>
                </div>
            @endif

            <!-- Wellness Overview Tab -->
            @if($activeTab === 'wellness')
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">High Stress</div>
                        <div class="mt-2 text-3xl font-bold text-red-600">{{ $wellnessOverview['high_stress'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Moderate Stress</div>
                        <div class="mt-2 text-3xl font-bold text-yellow-600">{{ $wellnessOverview['moderate_stress'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Low Stress</div>
                        <div class="mt-2 text-3xl font-bold text-green-600">{{ $wellnessOverview['low_stress'] ?? 0 }}</div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- Leave Request Modal -->
    @if($showLeaveModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900">Request Leave</h3>
                    <form wire:submit="requestLeave" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select wire:model="leaveForm.type" class="mt-1 block w-full px-3 py-2 border rounded">
                                <option value="vacation">Vacation</option>
                                <option value="sick_leave">Sick Leave</option>
                                <option value="personal">Personal</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" wire:model="leaveForm.start_date" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" wire:model="leaveForm.end_date" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reason</label>
                            <textarea wire:model="leaveForm.reason" class="mt-1 block w-full px-3 py-2 border rounded"></textarea>
                        </div>
                        <div class="flex justify-end space-x-2 mt-6">
                            <button type="button" wire:click="closeLeaveModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Create Shift Modal -->
    @if($showShiftModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900">Create Shift</h3>
                    <form wire:submit="createShift" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employee</label>
                            <input type="number" wire:model="shiftForm.employee_id" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="datetime-local" wire:model="shiftForm.start_date" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="datetime-local" wire:model="shiftForm.end_date" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Shift Type</label>
                            <select wire:model="shiftForm.shift_type" class="mt-1 block w-full px-3 py-2 border rounded">
                                <option value="regular">Regular</option>
                                <option value="night">Night</option>
                                <option value="overtime">Overtime</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-2 mt-6">
                            <button type="button" wire:click="closeShiftModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
