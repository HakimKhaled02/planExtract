<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Projects') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-end">
                <a href="{{ route('projects.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Upload New Images
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="p-0 sm:p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50/80 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-3 py-2 font-semibold tracking-wider whitespace-nowrap bg-gray-50/80 dark:bg-gray-900/50 sticky left-0 z-10 shadow-[1px_0_0_0_#e5e7eb] dark:shadow-[1px_0_0_0_#374151] backdrop-blur-sm border-r border-gray-200 dark:border-gray-700">Actions</th>
                                    
                                    @php
                                        $sampleColumns = collect();
                                        foreach($projects as $p) {
                                            if($p->columns->count() > 0) {
                                                $sampleColumns = $p->columns;
                                                break;
                                            }
                                        }
                                    @endphp
                                    
                                    @foreach($sampleColumns as $column)
                                        <th class="px-3 py-2 font-semibold tracking-wider whitespace-nowrap border-r border-gray-200 dark:border-gray-700 {{ strtoupper($column->name) === 'NO' ? 'w-10 min-w-[2.5rem] text-center' : '' }}">
                                            {{ $column->name }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-800">
                                @forelse($projects as $project)
                                    @if($project->rows->count() > 0)
                                        @foreach($project->rows as $index => $row)
                                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors">
                                                @if($index === 0)
                                                    <td rowspan="{{ $project->rows->count() }}" class="px-3 py-2 whitespace-nowrap space-x-1 bg-white dark:bg-gray-800 sticky left-0 z-10 shadow-[1px_0_0_0_#e5e7eb] dark:shadow-[1px_0_0_0_#374151] border-r border-b border-gray-200 dark:border-gray-700 align-top">
                                                        <!-- Edit -->
                                                        <a href="{{ route('projects.edit', $project) }}" class="inline-block p-1 text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded dark:text-indigo-400 dark:hover:text-indigo-300 dark:hover:bg-indigo-900/30 transition-all" title="Edit">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                        </a>
                                                        <!-- Delete -->
                                                        <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline" x-data @submit.prevent="ConfirmDialog('This action cannot be undone.', () => $el.submit(), { title: 'Delete this record?', confirmText: 'Yes, delete', confirmColor: '#dc2626', icon: 'warning', iconColor: '#f87171' })">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/30 transition-all" title="Delete">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                            </button>
                                                        </form>
                                                        <!-- History -->
                                                        <a href="{{ route('projects.history', $project) }}" class="inline-block p-1 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-all" title="History">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        </a>
                                                        <!-- Travel Allowance Claim -->
                                                        <a href="{{ route('projects.travel-claim', $project) }}" class="inline-block p-1 text-green-600 hover:text-green-800 hover:bg-green-50 rounded dark:text-green-400 dark:hover:text-green-300 dark:hover:bg-green-900/30 transition-all" title="Travel Allowance Claim">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                                                        </a>
                                                    </td>
                                                @endif
                                                
                                                @foreach($project->columns as $column)
                                                    @php
                                                        $cellValue = $row->cellValues->where('column_id', $column->id)->first();
                                                        $isGrouped = $column->order <= 7;
                                                    @endphp
                                                    
                                                    @if($isGrouped)
                                                        @if($index === 0)
                                                            @if($column->name === 'Company')
                                                                <td rowspan="{{ $project->rows->count() }}" class="px-3 py-2 text-gray-600 dark:text-gray-300 border-r border-b border-gray-200 dark:border-gray-700 whitespace-normal break-words min-w-[200px] align-top bg-white dark:bg-gray-800">
                                                                    {{ $cellValue ? $cellValue->value : '-' }}
                                                                </td>
                                                            @elseif($column->name === 'Address')
                                                                <td rowspan="{{ $project->rows->count() }}" class="px-3 py-2 text-gray-600 dark:text-gray-300 border-r border-b border-gray-200 dark:border-gray-700 whitespace-normal break-words min-w-[300px] align-top bg-white dark:bg-gray-800">
                                                                    {{ $cellValue ? $cellValue->value : '-' }}
                                                                </td>
                                                            @elseif($column->name === 'PIC')
                                                                <td rowspan="{{ $project->rows->count() }}" class="px-3 py-2 text-gray-600 dark:text-gray-300 border-r border-b border-gray-200 dark:border-gray-700 whitespace-normal break-words min-w-[150px] align-top bg-white dark:bg-gray-800">
                                                                    {{ $cellValue ? $cellValue->value : '-' }}
                                                                </td>
                                                            @elseif(strtoupper($column->name) === 'NO')
                                                                <td rowspan="{{ $project->rows->count() }}" class="px-3 py-2 text-gray-600 dark:text-gray-300 border-r border-b border-gray-200 dark:border-gray-700 whitespace-nowrap text-center align-top bg-white dark:bg-gray-800 w-10 min-w-[2.5rem]">
                                                                    {{ $cellValue ? $cellValue->value : '-' }}
                                                                </td>
                                                            @else
                                                                <td rowspan="{{ $project->rows->count() }}" class="px-3 py-2 text-gray-600 dark:text-gray-300 whitespace-normal break-words border-r border-b border-gray-200 dark:border-gray-700 min-w-[150px] align-top bg-white dark:bg-gray-800" title="{{ $cellValue ? $cellValue->value : '' }}">
                                                                    {{ $cellValue ? $cellValue->value : '-' }}
                                                                </td>
                                                            @endif
                                                        @endif
                                                    @else
                                                        @if($column->name === 'Status')
                                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                                                <div x-data="{ 
                                                                        open: false,
                                                                        status: '{{ $cellValue ? $cellValue->value : '' }}',
                                                                        isUpdating: false,
                                                                        dropdownStyle: '',
                                                                        updateStatus(newStatus) {
                                                                            this.status = newStatus;
                                                                            this.open = false;
                                                                            this.isUpdating = true;
                                                                            fetch('{{ route('projects.cells.update', $cellValue->id) }}', {
                                                                                method: 'PATCH',
                                                                                headers: {
                                                                                    'Content-Type': 'application/json',
                                                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                                                },
                                                                                body: JSON.stringify({ value: this.status })
                                                                            }).then(res => res.json()).then(data => {
                                                                                this.isUpdating = false;
                                                                                const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 1500});
                                                                                Toast.fire({icon: 'success', title: 'Status updated'});
                                                                            }).catch(() => {
                                                                                this.isUpdating = false;
                                                                            });
                                                                        },
                                                                        toggleDropdown() {
                                                                            if (this.open) {
                                                                                this.open = false;
                                                                            } else {
                                                                                const rect = this.$refs.button.getBoundingClientRect();
                                                                                const spaceBelow = window.innerHeight - rect.bottom;
                                                                                const dropdownHeight = 220;
                                                                                if (spaceBelow < dropdownHeight && rect.top > dropdownHeight) {
                                                                                    this.dropdownStyle = `position: fixed; bottom: ${window.innerHeight - rect.top + 4}px; left: ${rect.left}px; width: ${rect.width}px;`;
                                                                                } else {
                                                                                    this.dropdownStyle = `position: fixed; top: ${rect.bottom + 4}px; left: ${rect.left}px; width: ${rect.width}px;`;
                                                                                }
                                                                                this.open = true;
                                                                            }
                                                                        }
                                                                    }" class="relative">
                                                                    
                                                                    <!-- Button -->
                                                                    <button x-ref="button" @click="toggleDropdown()" @click.away="open = false" @scroll.window.passive="open = false" type="button"
                                                                            :disabled="isUpdating"
                                                                            :class="{
                                                                                'bg-yellow-300 text-black': status === 'Waiting Insp Date',
                                                                                'bg-pink-500 text-white': status === 'Waiting Inspection',
                                                                                'bg-emerald-700 text-white': status === 'Inspection Done',
                                                                                'bg-red-700 text-white': status === 'Failed',
                                                                                'bg-blue-700 text-white': status === 'Waiting Report',
                                                                                'bg-[#4B3B2B] text-[#FFE8C4]': status === 'Others',
                                                                                'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white': !status,
                                                                                'opacity-50': isUpdating
                                                                            }"
                                                                            class="flex items-center justify-between w-full text-[10px] font-bold rounded-full px-3 py-1 cursor-pointer transition-all min-w-[130px] border border-transparent hover:ring-2 hover:ring-offset-1 hover:ring-indigo-500">
                                                                        <span x-text="status ? status : '- Select -'"></span>
                                                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                                    </button>
                                                                    
                                                                    <!-- Dropdown Menu (Fixed positioned) -->
                                                                    <div x-show="open" :style="dropdownStyle" style="display: none;"
                                                                        x-transition:enter="transition ease-out duration-100"
                                                                        x-transition:enter-start="transform opacity-0 scale-95"
                                                                        x-transition:enter-end="transform opacity-100 scale-100"
                                                                        x-transition:leave="transition ease-in duration-75"
                                                                        x-transition:leave-start="transform opacity-100 scale-100"
                                                                        x-transition:leave-end="transform opacity-0 scale-95"
                                                                        class="fixed z-[9999] bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none p-1.5 space-y-1">
                                                                        
                                                                        <div @click="updateStatus('')" class="cursor-pointer text-[10px] font-bold px-2 py-1.5 rounded bg-gray-100 text-gray-900 hover:opacity-80 dark:bg-gray-700 dark:text-white">- Select -</div>
                                                                        <div @click="updateStatus('Waiting Insp Date')" class="cursor-pointer text-[10px] font-bold px-2 py-1.5 rounded bg-yellow-300 text-black hover:opacity-80">Waiting Insp Date</div>
                                                                        <div @click="updateStatus('Waiting Inspection')" class="cursor-pointer text-[10px] font-bold px-2 py-1.5 rounded bg-pink-500 text-white hover:opacity-80">Waiting Inspection</div>
                                                                        <div @click="updateStatus('Inspection Done')" class="cursor-pointer text-[10px] font-bold px-2 py-1.5 rounded bg-emerald-700 text-white hover:opacity-80">Inspection Done</div>
                                                                        <div @click="updateStatus('Failed')" class="cursor-pointer text-[10px] font-bold px-2 py-1.5 rounded bg-red-700 text-white hover:opacity-80">Failed</div>
                                                                        <div @click="updateStatus('Waiting Report')" class="cursor-pointer text-[10px] font-bold px-2 py-1.5 rounded bg-blue-700 text-white hover:opacity-80">Waiting Report</div>
                                                                        <div @click="updateStatus('Others')" class="cursor-pointer text-[10px] font-bold px-2 py-1.5 rounded bg-[#4B3B2B] text-[#FFE8C4] hover:opacity-80">Others</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        @elseif($column->name === 'Company')
                                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-700 whitespace-normal break-words min-w-[200px]">
                                                                {{ $cellValue ? $cellValue->value : '-' }}
                                                            </td>
                                                        @elseif($column->name === 'Address')
                                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-700 whitespace-normal break-words min-w-[300px]">
                                                                {{ $cellValue ? $cellValue->value : '-' }}
                                                            </td>
                                                        @elseif($column->name === 'PIC')
                                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-700 whitespace-normal break-words min-w-[150px]">
                                                                {{ $cellValue ? $cellValue->value : '-' }}
                                                            </td>
                                                        @elseif(strtoupper($column->name) === 'NO')
                                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap text-center w-10 min-w-[2.5rem]">
                                                                {{ $cellValue ? $cellValue->value : '-' }}
                                                            </td>
                                                        @else
                                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300 whitespace-normal break-words border-r border-gray-200 dark:border-gray-700 min-w-[150px]" title="{{ $cellValue ? $cellValue->value : '' }}">
                                                                {{ $cellValue ? $cellValue->value : '-' }}
                                                            </td>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors">
                                            <td class="px-3 py-2 whitespace-nowrap space-x-1 bg-white dark:bg-gray-800 group-hover:bg-gray-50/80 dark:group-hover:bg-gray-700/50 sticky left-0 z-10 shadow-[1px_0_0_0_#e5e7eb] dark:shadow-[1px_0_0_0_#374151] border-r border-gray-200 dark:border-gray-700">
                                                <!-- Delete Project -->
                                                <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline" x-data @submit.prevent="ConfirmDialog('This action cannot be undone.', () => $el.submit(), { title: 'Delete this project?', confirmText: 'Yes, delete', confirmColor: '#dc2626', icon: 'warning', iconColor: '#f87171' })">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/30 transition-all" title="Delete Project">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            </td>
                                            
                                            <td colspan="{{ $sampleColumns->count() > 0 ? $sampleColumns->count() : 17 }}" class="px-3 py-2 text-gray-400 dark:text-gray-500 italic border-r border-gray-200 dark:border-gray-700">
                                                {{ $project->name }} - No data extracted.
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="100%" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                            No projects found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($projects->hasPages())
                        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                            {{ $projects->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
