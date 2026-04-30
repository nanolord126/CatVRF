<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Leave Management</h1>
                <div class="flex space-x-2">
                    <button wire:click="refresh" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Refresh</button>
                    <button wire:click="openModal" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">+ Request Leave</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div class="flex space-x-4">
            <select wire:model="filter" class="px-3 py-2 border rounded">
                <option value="all">All Types</option>
                <option value="vacation">Vacation</option>
                <option value="sick_leave">Sick Leave</option>
                <option value="personal">Personal</option>
                <option value="unpaid">Unpaid</option>
            </select>
            <select wire:model="statusFilter" class="px-3 py-2 border rounded">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>
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
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @if(isset($leaves['error']))
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-red-500">{{ $leaves['error'] }}</td>
                                </tr>
                            @elseif(empty($leaves))
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No leaves found</td>
                                </tr>
                            @else
                                @foreach($leaves as $leave)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $leave['employee_name'] ?? '' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst($leave['type'] ?? '') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $leave['start_date'] ?? '' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $leave['end_date'] ?? '' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ match($leave['status'] ?? '') {
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'approved' => 'bg-green-100 text-green-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                                'cancelled' => 'bg-gray-100 text-gray-800',
                                                default => 'bg-gray-100 text-gray-800',
                                            } }}">
                                                {{ ucfirst($leave['status'] ?? '') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if($leave['status'] === 'pending')
                                                <button wire:click="approveLeave({{ $leave['id'] }})" class="text-green-600 hover:text-green-900 mr-3">Approve</button>
                                                <button wire:click="rejectLeave({{ $leave['id'] }})" class="text-red-600 hover:text-red-900">Reject</button>
                                            @elseif($leave['status'] === 'approved')
                                                <button wire:click="cancelLeave({{ $leave['id'] }})" class="text-yellow-600 hover:text-yellow-900">Cancel</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Request Leave Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900">Request Leave</h3>
                    <form wire:submit="submitLeave" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select wire:model="form.type" class="mt-1 block w-full px-3 py-2 border rounded">
                                <option value="vacation">Vacation</option>
                                <option value="sick_leave">Sick Leave</option>
                                <option value="personal">Personal</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" wire:model="form.start_date" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" wire:model="form.end_date" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reason</label>
                            <textarea wire:model="form.reason" class="mt-1 block w-full px-3 py-2 border rounded"></textarea>
                        </div>
                        <div class="flex justify-end space-x-2 mt-6">
                            <button type="button" wire:click="closeModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
