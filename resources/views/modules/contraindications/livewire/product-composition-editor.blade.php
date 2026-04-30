<div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Product Composition</h3>
        
        <form wire:submit="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ingredients</label>
                    <input type="text" wire:model="ingredients" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Enter ingredients separated by commas">
                    @error('ingredients') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Calories per 100g</label>
                    <input type="number" wire:model="caloriesPer100g" step="0.01" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proteins (g)</label>
                    <input type="number" wire:model="proteins" step="0.01" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fats (g)</label>
                    <input type="number" wire:model="fats" step="0.01" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Carbs (g)</label>
                    <input type="number" wire:model="carbs" step="0.01" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Allergens</label>
                    <input type="text" wire:model="allergens" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Enter allergens separated by commas (e.g., gluten, dairy, nuts)">
                    @error('allergens') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    <p class="text-xs text-gray-500 mt-1">Common allergens present in this product</p>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    {{ $isEditing ? 'Update' : 'Save' }} Composition
                </button>
            </div>
        </form>
    </div>
    
    @if($isEditing)
        <div class="mt-4 p-4 bg-blue-50 text-blue-700 rounded-md">
            <strong>Editing existing composition</strong>
        </div>
    @endif
    
    @session('composition-saved')
        <div class="mt-4 p-4 bg-green-100 text-green-700 rounded-md">
            Composition saved successfully
        </div>
    @endsession
</div>
