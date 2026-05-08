@props(['name', 'id' => null, 'label' => 'Upload File', 'accept' => '*/*', 'currentUrl' => null])

@php
    $id = $id ?? $name;
@endphp

<div class="mb-4">
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $label }}</label>
    @endif
    
    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-xl hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors relative bg-gray-50 dark:bg-gray-800/50"
         x-data="{ isDragging: false, fileSelected: false, fileName: '' }"
         @dragover.prevent="isDragging = true"
         @dragleave.prevent="isDragging = false"
         @drop.prevent="isDragging = false; const files = $event.dataTransfer.files; if(files.length) { $refs.fileInput.files = files; fileSelected = true; fileName = files[0].name; }"
         :class="{'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': isDragging}">
        
        <div class="space-y-2 text-center">
            @if($currentUrl)
                <div x-show="!fileSelected" class="mb-4 flex justify-center">
                    <img src="{{ $currentUrl }}" alt="Current image" class="h-24 w-24 rounded-full object-cover border-4 border-white dark:border-gray-800 shadow-md">
                </div>
            @endif
            
            <svg x-show="!fileSelected && !{{ $currentUrl ? 'true' : 'false' }}" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <svg x-show="fileSelected" class="mx-auto h-12 w-12 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            
            <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                <label for="{{ $id }}" class="relative cursor-pointer rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                    <span x-show="!fileSelected">{{ $currentUrl ? 'Change image' : 'Upload a file' }}</span>
                    <span x-show="fileSelected" x-text="'Selected file: ' + fileName" style="display: none;"></span>
                    <input id="{{ $id }}" name="{{ $name }}" type="file" accept="{{ $accept }}" class="sr-only" x-ref="fileInput" @change="fileSelected = true; fileName = $refs.fileInput.files[0].name">
                </label>
                <p class="pl-1" x-show="!fileSelected">or drag and drop</p>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400" x-show="!fileSelected">
                Select any file type up to 10MB
            </p>
        </div>
    </div>
    @error($name)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
