@props([
    'name',
    'value'       => '',
    'id'          => null,
    'placeholder' => 'Select date',
    'class'       => '',
])

{{--
    Usage:
    <x-date-picker name="cells[{{ $cell->id }}]" value="{{ $cell->value }}" />

    Accepts any extra attributes (e.g. required, disabled) via $attributes.
    Stored value format: YYYY-MM-DD (native <input type="date"> default).
    Clicking ANYWHERE on the widget opens the calendar (uses showPicker API with click fallback).
--}}

<div
    x-data="{
        date: '{{ $value }}',
        open() {
            const inp = this.$refs.inp;
            try { inp.showPicker(); } catch(e) { inp.click(); }
        },
        get display() {
            if (!this.date) return '';
            const [y, m, d] = this.date.split('-');
            if (!y || !m || !d) return this.date;
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return d + ' ' + months[parseInt(m) - 1] + ' ' + y;
        }
    }"
    class="relative w-full {{ $class }}"
    @click="open()"
>
    {{-- Invisible native input — it still handles keyboard & calendar rendering --}}
    <input
        x-ref="inp"
        type="date"
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        x-model="date"
        tabindex="-1"
        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
        {{ $attributes->except(['name', 'value', 'id', 'placeholder', 'class']) }}
    >

    {{-- Styled visible face --}}
    <div class="flex items-center w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-md shadow-sm px-3 py-2 text-sm cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition-colors select-none">
        <svg class="w-4 h-4 mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span
            x-text="display || '{{ $placeholder }}'"
            :class="date ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500'"
        ></span>
    </div>
</div>
