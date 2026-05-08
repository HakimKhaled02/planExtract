<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('New Extraction Project') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-100 dark:border-gray-700">
                <div class="p-8">
                    <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Project Name</label>
                            <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="e.g., Q1 Financial Report">
                        </div>

                        <div class="mb-8" x-data="{ fileCount: 0 }">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Upload Images</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md hover:border-indigo-500 transition-colors"
                                 :class="fileCount > 0 ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-500' : ''">
                                <div class="space-y-1 text-center">
                                    <svg x-show="fileCount === 0" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <svg x-show="fileCount > 0" style="display: none;" class="mx-auto h-12 w-12 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center items-center">
                                        <label for="images" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span x-text="fileCount > 0 ? 'Change files' : 'Upload files'">Upload files</span>
                                            <input id="images" name="images[]" type="file" multiple accept="image/*" class="sr-only" required @change="fileCount = $refs.file.files.length" x-ref="file">
                                        </label>
                                        <p class="pl-1" x-show="fileCount === 0">or drag and drop</p>
                                    </div>
                                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 mt-2" x-show="fileCount > 0" x-text="fileCount + ' image(s) selected'" style="display: none;"></p>
                                    <p class="text-xs text-gray-500" x-show="fileCount === 0">PNG, JPG, GIF up to 20MB</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-4">
                            <a href="{{ route('projects.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</a>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Create & Extract</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
