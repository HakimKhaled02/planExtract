<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Edit Data: ') }} {{ $project->name }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-3 flex justify-end">
                <a href="{{ route('projects.index') }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    {{ __('Back to Projects') }}
                </a>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="max-w-xl">
                            <x-input-label for="name" :value="__('Project Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $project->name)" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        
                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-md">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                    <tr>
                                        @foreach($project->columns as $column)
                                            <th class="px-3 py-3 border-r border-gray-200 dark:border-gray-700 whitespace-nowrap {{ strtoupper($column->name) === 'NO' ? 'w-10 min-w-[2.5rem] text-center' : '' }}">
                                                {{ $column->name }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($project->rows as $row)
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                            @foreach($project->columns as $column)
                                                @php
                                                    $cell      = $row->cellValues->where('column_id', $column->id)->first();
                                                    $isNo      = strtoupper($column->name) === 'NO';
                                                    $isLong    = in_array($column->name, ['Company', 'Address', 'Remark']);
                                                    $isGrouped = $column->order <= 7;
                                                    $isDate    = $column->name === 'Inspection Date';
                                                    $isTime    = $column->name === 'Time';

                                                    $tdClass = 'min-w-[150px]';
                                                    if ($isNo)                           $tdClass = 'w-12 min-w-[3rem] text-center';
                                                    elseif ($column->name === 'Address') $tdClass = 'min-w-[300px]';
                                                    elseif ($column->name === 'Company') $tdClass = 'min-w-[250px]';
                                                    elseif ($isDate)                     $tdClass = 'min-w-[180px]';
                                                    elseif ($isTime)                     $tdClass = 'min-w-[210px]';

                                                    $inputClass = 'block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm sm:text-sm';

                                                    // Parse time for AM/PM picker
                                                    $tH = ''; $tM = '00'; $tP = 'AM';
                                                    if ($isTime && $cell && $cell->value) {
                                                        $stored = $cell->value;
                                                        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $stored, $tm)) {
                                                            $tH = (int)$tm[1]; $tM = $tm[2]; $tP = strtoupper($tm[3]);
                                                        } elseif (preg_match('/^(\d{1,2}):(\d{2})$/', $stored, $tm)) {
                                                            $h24 = (int)$tm[1]; $tM = $tm[2];
                                                            $tP  = $h24 >= 12 ? 'PM' : 'AM';
                                                            $tH  = $h24 > 12 ? $h24 - 12 : ($h24 == 0 ? 12 : $h24);
                                                        }
                                                    }
                                                @endphp

                                                @if($isGrouped)
                                                    @if($loop->parent->first)
                                                        <td rowspan="{{ $project->rows->count() }}" class="px-2 py-2 border-r border-b border-gray-200 dark:border-gray-700 {{ $tdClass }} align-top bg-white dark:bg-gray-800">
                                                            @if($cell)
                                                                @if($isNo)
                                                                    <input type="text" name="cells[{{ $cell->id }}]" value="{{ $cell->value }}" class="{{ $inputClass }} text-center">
                                                                @elseif($isDate)
                                                                    <x-date-picker name="cells[{{ $cell->id }}]" value="{{ $cell->value }}" />
                                                                @elseif($isTime)
                                                                    <div x-data="{
                                                                        h: '{{ $tH }}',
                                                                        m: '{{ $tM }}',
                                                                        p: '{{ $tP }}',
                                                                        get val() {
                                                                            return this.h ? String(this.h).padStart(2,'0') + ':' + String(this.m).padStart(2,'0') + ' ' + this.p : '';
                                                                        }
                                                                    }">
                                                                        <input type="hidden" name="cells[{{ $cell->id }}]" :value="val">
                                                                        <div class="flex items-center gap-1">
                                                                            <select x-model="h" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs py-1.5 px-1.5">
                                                                                <option value="">--</option>
                                                                                @for($hh = 1; $hh <= 12; $hh++)
                                                                                    <option value="{{ $hh }}">{{ str_pad($hh, 2, '0', STR_PAD_LEFT) }}</option>
                                                                                @endfor
                                                                            </select>
                                                                            <span class="text-gray-500 font-bold">:</span>
                                                                            <select x-model="m" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs py-1.5 px-1.5">
                                                                                @foreach(['00','05','10','15','20','25','30','35','40','45','50','55'] as $mm)
                                                                                    <option value="{{ $mm }}">{{ $mm }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                            <select x-model="p" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs py-1.5 px-1.5">
                                                                                <option value="AM">AM</option>
                                                                                <option value="PM">PM</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                @elseif($isLong)
                                                                    <textarea name="cells[{{ $cell->id }}]" rows="{{ $column->name === 'Address' ? 6 : 2 }}" class="{{ $inputClass }}">{{ $cell->value }}</textarea>
                                                                @else
                                                                    <input type="text" name="cells[{{ $cell->id }}]" value="{{ $cell->value }}" class="{{ $inputClass }}">
                                                                @endif
                                                            @endif
                                                        </td>
                                                    @endif
                                                @else
                                                    <td class="px-2 py-2 border-r border-gray-200 dark:border-gray-700 {{ $tdClass }} align-top">
                                                        @if($cell)
                                                            @if($column->name === 'Status')
                                                                <select name="cells[{{ $cell->id }}]" class="{{ $inputClass }}">
                                                                    <option value="" {{ $cell->value == '' ? 'selected' : '' }}>- Select -</option>
                                                                    <option value="Waiting Insp Date" {{ $cell->value == 'Waiting Insp Date' ? 'selected' : '' }}>Waiting Insp Date</option>
                                                                    <option value="Waiting Inspection" {{ $cell->value == 'Waiting Inspection' ? 'selected' : '' }}>Waiting Inspection</option>
                                                                    <option value="Inspection Done" {{ $cell->value == 'Inspection Done' ? 'selected' : '' }}>Inspection Done</option>
                                                                    <option value="Failed" {{ $cell->value == 'Failed' ? 'selected' : '' }}>Failed</option>
                                                                    <option value="Waiting Report" {{ $cell->value == 'Waiting Report' ? 'selected' : '' }}>Waiting Report</option>
                                                                    <option value="Others" {{ $cell->value == 'Others' ? 'selected' : '' }}>Others</option>
                                                                </select>
                                                            @elseif($isLong)
                                                                <textarea name="cells[{{ $cell->id }}]" rows="{{ $column->name === 'Address' ? 6 : 2 }}" class="{{ $inputClass }}">{{ $cell->value }}</textarea>
                                                            @else
                                                                <input type="text" name="cells[{{ $cell->id }}]" value="{{ $cell->value }}" class="{{ $inputClass }}">
                                                            @endif
                                                        @endif
                                                    </td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    @if($project->rows->count() === 0)
                                        <tr>
                                            <td colspan="{{ $project->columns->count() }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                                No rows extracted yet.
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center gap-4 mt-6">
                            <x-primary-button>{{ __('Save All Changes') }}</x-primary-button>
                            <a href="{{ route('projects.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
