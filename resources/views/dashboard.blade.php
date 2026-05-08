<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-100 leading-tight tracking-tight">
            {{ __('Overview Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Projects -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="p-6 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Projects</p>
                            <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $totalProjects }}</p>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl text-indigo-600 dark:text-indigo-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Pending Extractions -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="p-6 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pending</p>
                            <p class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">{{ $pending }}</p>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-xl text-amber-600 dark:text-amber-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Total Extracted Rows -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="p-6 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Rows</p>
                            <p class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">{{ $totalRows }}</p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl text-emerald-600 dark:text-emerald-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Total Columns -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="p-6 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Columns</p>
                            <p class="text-3xl font-extrabold text-purple-600 dark:text-purple-400 mt-2">{{ $totalColumns }}</p>
                        </div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-xl text-purple-600 dark:text-purple-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
                <!-- Recent Projects -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Projects</h3>
                        <a href="{{ route('projects.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            New Project
                        </a>
                    </div>
                    <div class="p-0">
                        @if($recentProjects->isEmpty())
                            <div class="p-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No projects</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by uploading a new PDF.</p>
                            </div>
                        @else
                            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($recentProjects as $project)
                                    <li class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <a href="{{ route('projects.show', $project) }}" class="flex items-center px-6 py-4">
                                            <div class="min-w-0 flex-1 flex items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="h-12 w-12 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center">
                                                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    </div>
                                                </div>
                                                <div class="min-w-0 flex-1 px-4 md:grid md:grid-cols-2 md:gap-4">
                                                    <div>
                                                        <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400 truncate">{{ $project->name }}</p>
                                                        <p class="mt-2 flex items-center text-xs text-gray-500 dark:text-gray-400">
                                                            <span class="truncate">Uploaded {{ $project->created_at->diffForHumans() }}</span>
                                                        </p>
                                                    </div>
                                                    <div class="hidden md:block">
                                                        <div>
                                                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                                                Status
                                                            </p>
                                                            <p class="mt-1 flex items-center text-xs text-gray-500">
                                                                @if($project->status === 'completed')
                                                                    <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-400">Completed</span>
                                                                @elseif($project->status === 'pending')
                                                                    <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-400">Pending</span>
                                                                @else
                                                                    <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ ucfirst($project->status) }}</span>
                                                                @endif
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="bg-gray-50 dark:bg-gray-800/50 px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                            <a href="{{ route('projects.index') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300">View all projects &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Activity Log</h3>
                    </div>
                    <div class="p-6">
                        <div class="flow-root">
                            <ul class="-mb-8">
                                @forelse($recentActivity as $activity)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                        <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            {{ ucfirst($activity->description) }} on <span class="font-medium text-gray-900 dark:text-gray-200">{{ class_basename($activity->subject_type) }}</span>
                                                        </p>
                                                    </div>
                                                    <div class="text-right text-xs whitespace-nowrap text-gray-500 dark:text-gray-500">
                                                        <time datetime="{{ $activity->created_at }}">{{ $activity->created_at->diffForHumans() }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center">No recent activity.</p>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
