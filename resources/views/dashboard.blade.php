<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <a href="{{ route('productions.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nueva Producción
            </a>
        </div>
    </x-slot>

    @inject('claimService', 'App\Services\ProductionClaimService')
    @php
        $user = auth()->user();
        $suggestedProductions = $claimService->suggestHistoricalProductions($user);
        $myProductions = $user->productions()->latest()->get();
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
                                            {{ $prod->productionType->name ?? 'Tesis' }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ $prod->academicPeriod->name ?? '' }}
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

            <!-- My Works Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            Mis Producciones Científicas
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Listado de trabajos y tesis en los que estás registrado como autor o tutor.
                        </p>
                    </div>

                    @if ($myProductions->isEmpty())
                        <div class="text-center py-12 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">No tienes producciones registradas</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Comienza subiendo tu primer trabajo haciendo clic en "Nueva Producción".</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Título</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Clasificación</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rol</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($myProductions as $prod)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 max-w-md truncate" title="{{ $prod->title }}">
                                                    <a href="{{ route('productions.show', $prod) }}" class="hover:text-blue-600 dark:hover:text-blue-400 hover:underline transition duration-150">
                                                        {{ $prod->title }}
                                                    </a>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <span>Autores: <strong>{{ $prod->authors }}</strong></span>
                                                    <span class="mx-1.5">•</span>
                                                    <span>Tutor: <strong>{{ $prod->tutor }}</strong></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-xs text-gray-900 dark:text-gray-100 font-medium">
                                                    {{ $prod->academicProgram->name ?? 'Programa no especificado' }}
                                                </div>
                                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                                    {{ $prod->productionType->name ?? 'Tesis' }} • {{ $prod->academicPeriod->name ?? 'N/A' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                    {{ $prod->pivot->role === 'author' ? 'Autor' : 'Tutor' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $statusColors = [
                                                        'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700/60 dark:text-gray-300',
                                                        'under_review' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                                                        'needs_corrections' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300',
                                                        'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300',
                                                        'published' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
                                                        'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/20 dark:text-rose-300',
                                                    ];
                                                    $statusLabels = [
                                                        'draft' => 'Borrador',
                                                        'under_review' => 'En Revisión',
                                                        'needs_corrections' => 'Requiere Correcciones',
                                                        'approved' => 'Aprobado',
                                                        'published' => 'Publicado',
                                                        'rejected' => 'Rechazado',
                                                    ];
                                                    $colorClass = $statusColors[$prod->workflow_state] ?? 'bg-gray-100 text-gray-800';
                                                    $label = $statusLabels[$prod->workflow_state] ?? $prod->workflow_state;
                                                @endphp
                                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colorClass }}">
                                                    {{ $label }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end space-x-2">
                                                    <!-- Draft actions -->
                                                    @if ($prod->workflow_state === 'draft' && $prod->pivot->role === 'author')
                                                        <!-- Submit Draft -->
                                                        <form action="{{ route('productions.submit-draft', $prod) }}" method="POST" onsubmit="return confirm('¿Estás seguro de enviar este borrador a revisión? Ya no podrás editarlo libremente.')">
                                                            @csrf
                                                            <button type="submit" class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold shadow-sm hover:shadow transition duration-150">
                                                                Enviar a Revisión
                                                            </button>
                                                        </form>

                                                        <!-- Delete Draft -->
                                                        <form action="{{ route('productions.destroy', $prod) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar de forma permanente este borrador?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="px-2.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded text-xs font-semibold shadow-sm hover:shadow transition duration-150">
                                                                Eliminar
                                                            </button>
                                                        </form>
                                                    @else
                                                        <a href="{{ route('productions.show', $prod) }}" class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded text-xs font-semibold shadow-sm hover:shadow transition duration-150">
                                                            Ver Detalles
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Welcome / Platform Guidelines Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-2">¡Bienvenido al SKMS de la Universidad de Margarita!</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Desde esta plataforma podrás gestionar de manera activa todo el ciclo de vida de tus trabajos de investigación y producciones científicas. Si tienes trabajos históricos sin vincular, usa la sección de sugerencias para reclamarlos.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
