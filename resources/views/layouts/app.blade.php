<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'PlanExtract') }}</title>

        <!-- Fonts -->
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-900">
        <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            @include('layouts.navigation')

            <!-- Main Content Area -->
            <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
                
                <!-- Top Header for Mobile & Page Title -->
                <header class="sticky top-0 z-30 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-700">
                    <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <span class="sr-only">Open sidebar</span>
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            @isset($header)
                                {{ $header }}
                            @endisset
                        </div>
                        
                        <!-- Top Right Dropdown (User Profile) -->
                        <div class="flex items-center">
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 focus:outline-none transition duration-150 ease-in-out">
                                    @if(Auth::user()->profile_image)
                                        <img src="{{ Storage::url(Auth::user()->profile_image) }}" alt="Avatar" class="w-8 h-8 rounded-full border border-gray-200 dark:border-gray-700 object-cover">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-semibold text-sm">
                                            {{ substr(Auth::user()->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div class="hidden sm:block">{{ Auth::user()->name }}</div>
                                    <div class="hidden sm:block">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                                <div x-show="open" @click.away="open = false" style="display: none;" class="absolute right-0 w-48 mt-2 origin-top-right rounded-lg shadow-xl py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-50 divide-y divide-gray-100 dark:divide-gray-700">
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">Profile Settings</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">
                                            Log Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
        
        <!-- Flash Messages & Global Confirm (SweetAlert2) -->
        <script>
            // Global modern Toast
            window.Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                customClass: {
                    popup: 'rounded-xl shadow-lg border border-gray-100 text-sm font-medium',
                    timerProgressBar: 'bg-indigo-400',
                },
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });

            // Global modern Confirm dialog
            window.ConfirmDialog = (message, onConfirm, options = {}) => {
                Swal.fire({
                    title: options.title || 'Are you sure?',
                    text: message,
                    icon: options.icon || 'warning',
                    showCancelButton: true,
                    confirmButtonText: options.confirmText || 'Yes, proceed',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: options.confirmColor || '#6366f1',
                    cancelButtonColor: '#e5e7eb',
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-gray-100',
                        title: 'text-base font-semibold text-gray-800',
                        htmlContainer: 'text-sm text-gray-500',
                        confirmButton: 'rounded-lg px-5 py-2 text-sm font-semibold text-white',
                        cancelButton: 'rounded-lg px-5 py-2 text-sm font-semibold !text-gray-700',
                    },
                    buttonsStyling: true,
                }).then((result) => {
                    if (result.isConfirmed) onConfirm();
                });
            };

            document.addEventListener('DOMContentLoaded', function() {
                @if(session('success'))
                    Toast.fire({ icon: 'success', title: "{{ addslashes(session('success')) }}" });
                @endif
                @if(session('error'))
                    Toast.fire({ icon: 'error', title: "{{ addslashes(session('error')) }}" });
                @endif
                @if(session('warning'))
                    Toast.fire({ icon: 'warning', title: "{{ addslashes(session('warning')) }}" });
                @endif
            });
        </script>
    </body>
</html>
