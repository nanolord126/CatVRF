<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">HRM Admin Dashboard</h1>
                <div class="flex space-x-2">
                    <button wire:click="refresh" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Refresh
                    </button>
                    <button wire:click="openCreateModal" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        + Add Employee
                    </button>
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
                <button wire:click="setActiveTab('employees')"
                        class="{{ $activeTab === 'employees' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Employees
                </button>
                <button wire:click="setActiveTab('analytics')"
                        class="{{ $activeTab === 'analytics' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Analytics
                </button>
                <button wire:click="setActiveTab('settings')"
                        class="{{ $activeTab === 'settings' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' }} border-b-2 py-2 px-1 text-sm font-medium">
                    Settings
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
                        <div class="text-sm font-medium text-gray-500">Total Employees</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total_employees'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Active</div>
                        <div class="mt-2 text-3xl font-bold text-green-600">{{ $stats['active_employees'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">On Leave</div>
                        <div class="mt-2 text-3xl font-bold text-yellow-600">{{ $stats['on_leave'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">New This Month</div>
                        <div class="mt-2 text-3xl font-bold text-blue-600">{{ $stats['new_this_month'] ?? 0 }}</div>
                    </div>
                </div>

                <div class="mt-8 bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
                    </div>
                    <div class="px-6 py-4">
                        @if(empty($recentActivity))
                            <p class="text-gray-500">No recent activity</p>
                        @else
                            <ul class="space-y-3">
                                @foreach($recentActivity as $activity)
                                    <li class="flex items-start">
                                        <div class="flex-shrink-0 h-2 w-2 mt-2 rounded-full bg-blue-600"></div>
                                        <div class="ml-3">
                                            <p class="text-sm text-gray-900">{{ $activity['description'] ?? '' }}</p>
                                            <p class="text-xs text-gray-500">{{ $activity['timestamp'] ?? '' }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Employees Tab -->
            @if($activeTab === 'employees')
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Employees</h3>
                            <div class="flex space-x-2">
                                <input type="text" wire:model="search" placeholder="Search..." class="px-3 py-2 border rounded">
                                <select wire:model="filterRole" class="px-3 py-2 border rounded">
                                    <option value="">All Roles</option>
                                    <option value="admin">Admin</option>
                                    <option value="manager">Manager</option>
                                    <option value="hr">HR</option>
                                    <option value="employee">Employee</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @if(isset($employees['data']) && count($employees['data']) > 0)
                                        @foreach($employees['data'] as $employee)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">{{ $employee['name'] ?? '' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">{{ $employee['email'] ?? '' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        {{ $employee['role'] ?? '' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">{{ $employee['status'] ?? '' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button wire:click="onboardEmployee({{ $employee['id'] }})" class="text-blue-600 hover:text-blue-900 mr-3">Onboard</button>
                                                    <button wire:click="deleteEmployee({{ $employee['id'] }})" class="text-red-600 hover:text-red-900">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No employees found</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Analytics Tab -->
            @if($activeTab === 'analytics')
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Analytics</h3>
                    <p class="text-gray-500">Analytics dashboard coming soon...</p>
                </div>
            @endif

            <!-- Settings Tab -->
            @if($activeTab === 'settings')
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Settings</h3>
                    <p class="text-gray-500">Settings panel coming soon...</p>
                </div>
            @endif
        @endif
    </div>

    <!-- Create Employee Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900">Add Employee</h3>
                    <form wire:submit="createEmployee" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" wire:model="createForm.first_name" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" wire:model="createForm.last_name" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" wire:model="createForm.email" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" wire:model="createForm.phone" class="mt-1 block w-full px-3 py-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <select wire:model="createForm.role" class="mt-1 block w-full px-3 py-2 border rounded">
                                <option value="admin">Admin</option>
                                <option value="manager">Manager</option>
                                <option value="hr">HR</option>
                                <option value="employee">Employee</option>
                                <option value="intern">Intern</option>
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
</div>
