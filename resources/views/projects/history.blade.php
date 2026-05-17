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
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-6">Activity History</h3>
                    
                    <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                        <table class="w-full text-sm text-left border-collapse">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-4 py-3 font-semibold whitespace-nowrap">Date & Time</th>
                                    <th class="px-4 py-3 font-semibold whitespace-nowrap">User</th>
                                    <th class="px-4 py-3 font-semibold whitespace-nowrap">Action</th>
                                    <th class="px-4 py-3 font-semibold whitespace-nowrap">Target Input</th>
                                    <th class="px-4 py-3 font-semibold min-w-[200px]">Old Value</th>
                                    <th class="px-4 py-3 font-semibold min-w-[200px]">New Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($activities as $activity)
                                    @php
                                        $target = '';
                                        if(class_basename($activity->subject_type) === 'CellValue') {
                                            $colName = $activity->subject && $activity->subject->column ? $activity->subject->column->name : 'Unknown Field';
                                            $rowNo = $activity->subject && $activity->subject->row ? $activity->subject->row->order : '?';
                                            $target = "Row {$rowNo} - {$colName}";
                                        } elseif(class_basename($activity->subject_type) === 'Project') {
                                            $target = "Project Details";
                                        }
                                        
                                        $properties = $activity->properties;
                                        $attributes = $properties['attributes'] ?? [];
                                        $old = $properties['old'] ?? [];
                                    @endphp
                                    @if(!empty($attributes))
                                        @foreach($attributes as $key => $newValue)
                                            @php
                                                $oldValue = $old[$key] ?? '';
                                            @endphp
                                            @if($oldValue != $newValue)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors bg-white dark:bg-gray-900/50">
                                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-xs">{{ $activity->created_at->format('d M Y, H:i:s') }}</td>
                                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ $activity->causer->name ?? 'System' }}</td>
                                                    <td class="px-4 py-3 whitespace-nowrap">
                                                        <span class="px-2 py-1 text-[10px] uppercase font-bold rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                                                            {{ $activity->description }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-indigo-600 dark:text-indigo-400 font-medium">{{ $target ?: ucfirst($key) }}</td>
                                                    <td class="px-4 py-3 text-red-600 dark:text-red-400 line-through break-words">{{ $oldValue ?: '-' }}</td>
                                                    <td class="px-4 py-3 text-emerald-600 dark:text-emerald-400 font-medium break-words">{{ $newValue ?: '-' }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @else
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors bg-white dark:bg-gray-900/50">
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-xs">{{ $activity->created_at->format('d M Y, H:i:s') }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900 dark:text-white">{{ $activity->causer->name ?? 'System' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="px-2 py-1 text-[10px] uppercase font-bold rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $activity->description }}</span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $target }}</td>
                                            <td class="px-4 py-3 text-gray-400">-</td>
                                            <td class="px-4 py-3 text-gray-400">-</td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-900/50">
                                            No history recorded for this project yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($activities->hasPages())
                        <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-4">
                            {{ $activities->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
