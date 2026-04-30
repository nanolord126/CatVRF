<div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Breed Certification Matrix</h2>
            <p class="text-sm text-gray-500 mt-1">
                Master: {{ $masterName ?? 'N/A' }}
            </p>
        </div>

        <div class="p-6">
            @if(empty($certificationMatrix))
                <div class="text-center py-12 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2">No breed certifications found</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Breed Group
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Required Level
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Certification Level
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Expiry Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Specialization Level
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Groomings Completed
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($certificationMatrix as $breedGroup => $data)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ ucfirst(str_replace('_', ' ', $breedGroup)) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ ucfirst($data['species']) }}
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ implode(', ', array_slice($data['breeds'], 0, 3)) }}
                                            @if(count($data['breeds']) > 3)
                                                ...
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            {{ ucfirst($data['required_level']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($data['certification_level'])
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $getCertificationLevelColor($data['certification_level']) }}">
                                                {{ ucfirst($data['certification_level']) }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">Not certified</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($data['expiry_date'])
                                            {{ \Carbon\Carbon::parse($data['expiry_date'])->format('M d, Y') }}
                                            @if(\Carbon\Carbon::parse($data['expiry_date'])->diffInDays(now(), false) < 30)
                                                <span class="ml-2 text-red-600 text-xs">(Expiring soon)</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($data['specialization_level'])
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $getSpecializationLevelColor($data['specialization_level']) }}">
                                                {{ ucfirst($data['specialization_level']) }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $data['groomings_completed'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($data['certification_level'] && $this->isCertificationValid($data))
                                            <span class="flex items-center text-green-600">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                Certified
                                            </span>
                                        @elseif($data['certification_level'] && !$this->isCertificationValid($data))
                                            <span class="flex items-center text-red-600">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                                Expired/Invalid
                                            </span>
                                        @else
                                            <span class="flex items-center text-gray-400">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                                                </svg>
                                                Not Certified
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
