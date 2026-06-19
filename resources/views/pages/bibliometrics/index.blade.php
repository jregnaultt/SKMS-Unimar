<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Análisis Bibliométrico
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Summary card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Producciones publicadas</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $metrics['total_published'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Programas académicos</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ count($metrics['by_program']) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Líneas de investigación</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ count($metrics['by_research_line']) }}</p>
                </div>
            </div>

            <!-- Yearly evolution -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6"
                 x-data="lineChart('yearlyEvolution', @js(array_column($metrics['yearly_evolution'], 'year')), @js(array_column($metrics['yearly_evolution'], 'total')), 'Evolución anual de publicaciones')">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Evolución temporal</h3>
                <div class="relative h-80">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- By program -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6"
                     x-data="barChart('byProgram', @js(array_column($metrics['by_program'], 'program')), @js(array_column($metrics['by_program'], 'total')), 'Producción por programa')">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Por programa académico</h3>
                    <div class="relative h-80">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </div>

                <!-- By research line -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6"
                     x-data="barChart('byResearchLine', @js(array_column($metrics['by_research_line'], 'line')), @js(array_column($metrics['by_research_line'], 'total')), 'Producción por línea de investigación')">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Por línea de investigación</h3>
                    <div class="relative h-80">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top tutors -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Top tutores</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tutor</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Producciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($metrics['top_tutors'] as $tutor)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $tutor['tutor'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 text-right">{{ $tutor['total'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">No hay datos disponibles.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top research lines -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Top líneas de investigación</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Línea</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Producciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($metrics['top_research_lines'] as $line)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $line['line'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 text-right">{{ $line['total'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">No hay datos disponibles.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @pushOnce('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                const commonOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                };

                Alpine.data('lineChart', (name, labels, data, labelText) => ({
                    init() {
                        new Chart(this.$refs.canvas, {
                            type: 'line',
                            data: {
                                labels,
                                datasets: [{
                                    label: labelText,
                                    data,
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    fill: true,
                                    tension: 0.3,
                                }],
                            },
                            options: {
                                ...commonOptions,
                                scales: {
                                    y: { beginAtZero: true, ticks: { precision: 0 } },
                                },
                            },
                        });
                    },
                }));

                Alpine.data('barChart', (name, labels, data, labelText) => ({
                    init() {
                        new Chart(this.$refs.canvas, {
                            type: 'bar',
                            data: {
                                labels,
                                datasets: [{
                                    label: labelText,
                                    data,
                                    backgroundColor: 'rgb(16, 185, 129)',
                                }],
                            },
                            options: {
                                ...commonOptions,
                                scales: {
                                    y: { beginAtZero: true, ticks: { precision: 0 } },
                                },
                            },
                        });
                    },
                }));
            });
        </script>
    @endPushOnce
</x-app-layout>
