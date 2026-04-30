<div>
    @if($isEditing)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">{{ $editingId ? 'Edit' : 'Add' }} Allergy</h3>
            
            <form wire:submit="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Allergen Name</label>
                        <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                        <select wire:model="severity" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="mild">Mild</option>
                            <option value="moderate">Moderate</option>
                            <option value="severe">Severe</option>
                            <option value="life_threatening">Life Threatening</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reaction Description</label>
                        <textarea wire:model="reaction" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Applicable Scopes</label>
                        <div class="flex flex-wrap gap-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model="scopes" value="cosmetology" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Cosmetology</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model="scopes" value="food" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Food</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model="scopes" value="medical" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Medical</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model="scopes" value="grooming" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Grooming</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model="scopes" value="fitness" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Fitness</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Leave empty to apply to all scopes</p>
                    </div>
                </div>
                
                <div class="mt-4 flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        {{ $editingId ? 'Update' : 'Add' }} Allergy
                    </button>
                    <button type="button" wire:click="cancel" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="mb-4">
            <button wire:click="$set('isEditing', true)" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Add Allergy
            </button>
        </div>
    @endif
    
    @if($allergies->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Allergen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scopes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($allergies as $allergy)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $allergy->name }}</div>
                                @if($allergy->reaction)
                                    <div class="text-sm text-gray-500">{{ $allergy->reaction }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($allergy->severity === 'mild') bg-green-100 text-green-800
                                    @elseif($allergy->severity === 'moderate') bg-yellow-100 text-yellow-800
                                    @elseif($allergy->severity === 'severe') bg-orange-100 text-orange-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($allergy->severity) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(empty($allergy->scopes))
                                    <span class="text-sm text-gray-500">All scopes</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($allergy->scopes as $scope)
                                            <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">{{ ucfirst($scope) }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button wire:click="edit({{ $allergy->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button wire:click="delete({{ $allergy->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{ $allergies->links() }}
    @else
        <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
            No allergies recorded
        </div>
    @endif
    
    @session('allergy-saved')
        <div class="mt-4 p-4 bg-green-100 text-green-700 rounded-md">
            {{ session('allergy-saved') }}
        </div>
    @endsession
    
    @session('allergy-deleted')
        <div class="mt-4 p-4 bg-red-100 text-red-700 rounded-md">
            {{ session('allergy-deleted') }}
        </div>
    @endsession
</div>
