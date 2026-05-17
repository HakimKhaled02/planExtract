<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Project History: ') }} {{ $project->name }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-3 flex justify-end">
                <a href="{{ route('projects.index') }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    {{ __('Back to Projects') }}
                </a>
            </div>

            @if(session('success'))
                <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('projects.history.delete', $project) }}" id="deleteForm" x-data="{ selected: [], allChecked: false, toggleAll(checked) { this.selected = checked ? [...document.querySelectorAll('.row-checkbox')].map(el => el.value) : []; this.allChecked = checked; } }">
                        @csrf
                        @method('DELETE')

                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium">Activity History</h3>
                            <button type="button"
                                x-show="selected.length > 0"
                                style="display:none"
                                @click="ConfirmDialog('Delete ' + selected.length + ' selected record(s)?', () => $el.closest('form').submit(), { title: 'Delete History?', confirmText: 'Yes, delete', confirmColor: '#dc2626', icon: 'warning', iconColor: '#f87171' })"
                                class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-red-700 transition ease-in-out duration-150">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Delete Selected (<span x-text="selected.length"></span>)
                            </button>
                        </div>

                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                    <tr>
                                        <th class="px-3 py-3 w-8">
                                            <input type="checkbox" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                @change="toggleAll($event.target.checked)"
                                                :checked="allChecked">
                                        </th>
                                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Date & Time</th>
                                        <th class="px-4 py-3 font-semibold whitespace-nowrap">User</th>
                                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Action</th>
                                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Column</th>
                                        <th class="px-4 py-3 font-semibold min-w-[180px]">Old Value</th>
                                        <th class="px-4 py-3 font-semibold min-w-[180px]">New Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse($activities as $activity)
                                        @php
                                            // Get column name from stored properties (most reliable)
                                            $colName = $activity->properties['column_name'] ?? '';
                                            if (!$colName) {
                                                if(class_basename($activity->subject_type) === 'CellValue') {
                                                    $colName = $activity->subject && $activity->subject->column ? $activity->subject->column->name : 'Unknown Field';
                                                } elseif(class_basename($activity->subject_type) === 'Project') {
                                                    $colName = 'Project Name';
                                                }
                                            }

                                            $properties = $activity->properties;
                                            $attributes = $properties['attributes'] ?? [];
                                            $old = $properties['old'] ?? [];

                                            // Resolve old/new value: for cells it's 'value', for project it's 'name'
                                            $oldVal = $old['value'] ?? $old['name'] ?? '';
                                            $newVal = $attributes['value'] ?? $attributes['name'] ?? '';
                                        @endphp
                                        @if($oldVal !== '' || $newVal !== '')
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors bg-white dark:bg-gray-900/50">
                                                <td class="px-3 py-2.5">
                                                    <input type="checkbox" name="ids[]" value="{{ $activity->id }}"
                                                        class="row-checkbox w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                        x-model="selected"
                                                        :value="'{{ $activity->id }}'">
                                                </td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-gray-500 text-xs">{{ $activity->created_at->format('d M Y, H:i') }}</td>
                                                <td class="px-4 py-2.5 whitespace-nowrap font-medium text-gray-900 dark:text-white text-xs">{{ $activity->causer->name ?? 'System' }}</td>
                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                    <span class="px-2 py-0.5 text-[10px] uppercase font-bold rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                                                        {{ $activity->description }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-indigo-600 dark:text-indigo-400 font-medium text-xs">{{ $colName }}</td>
                                                <td class="px-4 py-2.5 text-red-600 dark:text-red-400 line-through break-words text-xs">{{ $oldVal ?: '-' }}</td>
                                                <td class="px-4 py-2.5 text-emerald-600 dark:text-emerald-400 font-medium break-words text-xs">{{ $newVal ?: '-' }}</td>
                                            </tr>
                                        @else
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors bg-white dark:bg-gray-900/50">
                                                <td class="px-3 py-2.5">
                                                    <input type="checkbox" name="ids[]" value="{{ $activity->id }}"
                                                        class="row-checkbox w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                        x-model="selected"
                                                        :value="'{{ $activity->id }}'">
                                                </td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-gray-500 text-xs">{{ $activity->created_at->format('d M Y, H:i') }}</td>
                                                <td class="px-4 py-2.5 whitespace-nowrap font-medium text-gray-900 dark:text-white text-xs">{{ $activity->causer->name ?? 'System' }}</td>
                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                    <span class="px-2 py-0.5 text-[10px] uppercase font-bold rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $activity->description }}</span>
                                                </td>
                                                <td class="px-4 py-2.5 whitespace-nowrap text-gray-600 dark:text-gray-400 text-xs">{{ $colName }}</td>
                                                <td class="px-4 py-2.5 text-gray-400 text-xs">-</td>
                                                <td class="px-4 py-2.5 text-gray-400 text-xs">-</td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-900/50 text-sm">
                                                No history recorded for this project yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($activities->hasPages())
                            <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                                {{ $activities->links() }}
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
