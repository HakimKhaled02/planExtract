<!-- Sidebar Backdrop (Mobile) -->
<div x-show="sidebarOpen" class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

<!-- Sidebar -->
<div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-gray-900 border-r border-gray-100 dark:border-gray-800 shadow-sm transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto lg:h-full lg:w-72 flex flex-col">
    <!-- Logo -->
    <div class="flex items-center justify-center py-4 px-4 border-b border-gray-100 dark:border-gray-800">
        <a href="{{ route('dashboard') }}" class="flex items-center justify-center w-full">
            <img src="{{ asset('images/logo.png') }}" alt="PlanExtract Logo" class="w-56 h-auto max-h-20 object-contain">
        </a>
    </div>

    <!-- Navigation Links -->
    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
        <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Main Menu</p>
        
        <!-- Dashboard Link -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200 dark:shadow-none' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
            <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-indigo-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="font-semibold text-sm">Dashboard</span>
        </a>

        <!-- Projects Link -->
        <a href="{{ route('projects.index') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('projects.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200 dark:shadow-none' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}">
            <svg class="w-5 h-5 {{ request()->routeIs('projects.*') ? 'text-white' : 'text-gray-400 group-hover:text-indigo-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="font-semibold text-sm">Extraction Projects</span>
        </a>
    </div>

    <!-- Bottom Profile Area -->
    <div class="p-4 border-t border-gray-100 dark:border-gray-800">
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300">
            @if(Auth::user()->profile_image)
                <img src="{{ Storage::url(Auth::user()->profile_image) }}" alt="Profile" class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-700">
            @else
                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-semibold text-sm">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
            @endif
            <div class="flex flex-col">
                <span class="font-semibold text-sm">{{ Auth::user()->name }}</span>
                <span class="text-xs text-gray-500 truncate w-32">{{ Auth::user()->email }}</span>
            </div>
        </a>
    </div>
</div>
