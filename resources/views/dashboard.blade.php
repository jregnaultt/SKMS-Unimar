<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @inject('claimService', 'App\Services\ProductionClaimService')
    @php
        $suggestedProductions = $claimService->suggestHistoricalProductions(auth()->user());
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Notifications / Success or Error Alerts -->
            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border-l-4 border-emerald-500 rounded-r-lg shadow-sm text-emerald-800 dark:text-emerald-300 transition-all duration-300 animate-fade-in-down">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border-l-4 border-rose-500 rounded-r-lg shadow-sm text-rose-800 dark:text-rose-300 transition-all duration-300 animate-fade-in-down">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Suggested Productions Section -->
            @if ($suggestedProductions->isNotEmpty())
                <div class="bg-gradient-to-r from-blue-500/10 via-indigo-500/5 to-transparent dark:from-blue-950/20 dark:via-indigo-950/10 dark:to-transparent border border-blue-100 dark:border-blue-900/50 rounded-2xl p-6 shadow-md">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="flex h-2.5 w-2.5 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                                </span>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    Trabajos científicos sugeridos
                                </h3>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Encontramos tesis históricas cargadas en el sistema cuyos autores o tutor coinciden parcialmente con tu nombre. Puedes reclamar tu vinculación oficial.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($suggestedProductions as $prod)
                            <div class="bg-white dark:bg-gray-800/60 backdrop-blur-sm border border-gray-100 dark:border-gray-700/50 rounded-xl p-5 hover:shadow-lg hover:border-blue-400 dark:hover:border-blue-500/50 transition-all duration-300 flex flex-col justify-between">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-start space-x-2">
                                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $prod->productionType->nombre ?? 'Tesis' }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ $prod->academicPeriod->nombre ?? '' }}
                                        </span>
                                    </div>

                                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 line-clamp-2">
                                        {{ $prod->title }}
                                    </h4>

                                    <div class="space-y-1.5 text-xs text-gray-600 dark:text-gray-400">
                                        <div class="flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                            <span class="truncate">Autores: <strong>{{ $prod->authors ?? 'No especificado' }}</strong></span>
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            <span class="truncate">Tutor: <strong>{{ $prod->tutor ?? 'No especificado' }}</strong></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-end space-x-2">
                                    <!-- Reclamar como Autor -->
                                    <form action="{{ route('claims.store') }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas reclamar la autoría (estudiante) de este trabajo científico?')">
                                        @csrf
                                        <input type="hidden" name="production_id" value="{{ $prod->id }}">
                                        <input type="hidden" name="role" value="author">
                                        <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-sm hover:shadow transition-all duration-200">
                                            Reclamar Autoría
                                        </button>
                                    </form>

                                    <!-- Reclamar como Tutor -->
                                    <form action="{{ route('claims.store') }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas reclamar la tutoría de este trabajo científico?')">
                                        @csrf
                                        <input type="hidden" name="production_id" value="{{ $prod->id }}">
                                        <input type="hidden" name="role" value="tutor">
                                        <button type="submit" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-xs font-semibold transition-all duration-200">
                                            Reclamar Tutoría
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Main Content / Profile Info -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-2">¡Bienvenido al SKMS de la Universidad de Margarita!</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Has iniciado sesión de manera segura. Desde aquí podrás gestionar tus producciones científicas, consultar catálogos y hacer seguimiento de tus procesos de investigación.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
