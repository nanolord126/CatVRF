<div x-data="{
    isDragging: false,
}" x-init="
    $el.addEventListener('dragenter', () => isDragging = true);
    $el.addEventListener('dragleave', () => isDragging = false);
    $el.addEventListener('drop', () => isDragging = false);
" class="space-y-4">
    <!-- Upload Zone -->
    <div
        class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
        :class="{
            'border-blue-500 bg-blue-50': isDragging,
            'border-gray-300 hover:border-gray-400': !isDragging
        }"
        @dragover.prevent
        @drop.prevent="$wire.upload()"
    >
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
        </svg>
        <p class="mt-2 text-sm text-gray-600">
            Drag and drop files here, or
            <label class="text-blue-600 hover:text-blue-700 cursor-pointer">
                browse
                <input
                    type="file"
                    wire:model="files"
                    {{ $multiple ? 'multiple' : '' }}
                    accept="{{ $this->acceptedFileTypes }}"
                    class="hidden"
                >
            </label>
        </p>
        <p class="mt-1 text-xs text-gray-500">
            Max size: {{ $this->formatSize($this->maxFileSize) }}
        </p>
    </div>

    <!-- Upload Progress -->
    @if($isUploading)
    <div class="bg-gray-100 rounded-lg p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Uploading...</span>
            <span class="text-sm text-gray-500">{{ $uploadProgress }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                style="width: {{ $uploadProgress }}%"
            ></div>
        </div>
    </div>
    @endif

    <!-- Uploaded Files -->
    @if(!empty($uploadedFiles))
    <div class="space-y-2">
        <h3 class="text-sm font-medium text-gray-700">Uploaded Files</h3>
        @foreach($uploadedFiles as $index => $file)
        <div class="flex items-center justify-between bg-white border rounded-lg p-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $file['name'] }}</p>
                    <p class="text-xs text-gray-500">{{ $file['size'] }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ $file['url'] }}"
                    target="_blank"
                    class="text-blue-600 hover:text-blue-700 text-sm"
                >
                    View
                </a>
                <button
                    wire:click="deleteMedia('{{ $file['id'] }}')"
                    class="text-red-600 hover:text-red-700 text-sm"
                >
                    Delete
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Errors -->
    @if(!empty($errors))
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-red-800 mb-2">Errors</h4>
        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
            @foreach($errors as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Upload Button -->
    @if(!$autoUpload && !$isUploading)
    <button
        wire:click="upload"
        wire:loading.attr="disabled"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors disabled:opacity-50"
    >
        Upload Files
    </button>
    @endif
</div>
