@php
    // Determine active role from session or default to the user's first role
    $activeRole = session('active_dashboard_role', auth()->user()->getRoleNames()->first() ?? 'Estudiante');
    $roles = auth()->user()->getRoleNames()->toArray();
@endphp

<x-dashboard-layout :roles="$roles" :activeRole="$activeRole">
    <!-- Main Content Wrapper -->
    <div class="py-6 space-y-8">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
            <div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center text-[10px] font-bold text-slate-400 hover:text-[#0d4d98] mb-2 transition duration-150 uppercase tracking-wider">
                    <svg class="w-3.5 h-3.5 mr-1.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver a la Cabina de Control
                </a>
                <h2 class="font-extrabold text-2xl text-slate-800 leading-tight">
                    Análisis Bibliométrico
                </h2>
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Indicadores de productividad y rendimiento científico</p>
            </div>
            <div>
                <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center px-4 py-2 bg-[#0d4d98] hover:bg-blue-800 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m3.243-9.757a3 3 0 114.243 4.243M9 12.05A8.001 8.001 0 0117 20h3M15 8.05A8.001 8.001 0 0122 15m-6 2l-3-3m0 0l-3 3m3-3V20"></path>
                    </svg>
                    Generar Reportes
                </a>
            </div>
        </div>

        <!-- KPI Cards Grid (Three columns, light-only premium design) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Metric Card 1: Total Published -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4 border-t-4 border-t-[#0d4d98]">
                <div class="p-3 bg-[#0d4d98]/10 text-[#0d4d98] rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Producciones Publicadas</p>
                    <p class="text-3xl font-black text-slate-800 mt-0.5 leading-tight">{{ $metrics['total_published'] }}</p>
                </div>
            </div>

            <!-- Metric Card 2: Academic Programs -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4 border-t-4 border-t-[#0d4d98]">
                <div class="p-3 bg-[#0d4d98]/10 text-[#0d4d98] rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Programas Académicos</p>
                    <p class="text-3xl font-black text-slate-800 mt-0.5 leading-tight">{{ count($metrics['by_program']) }}</p>
                </div>
            </div>

            <!-- Metric Card 3: Research Lines -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4 border-t-4 border-t-[#0d4d98]">
                <div class="p-3 bg-[#0d4d98]/10 text-[#0d4d98] rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-450 uppercase tracking-wider">Líneas de Investigación</p>
                    <p class="text-3xl font-black text-slate-800 mt-0.5 leading-tight">{{ count($metrics['by_research_line']) }}</p>
                </div>
            </div>
        </div>

        <!-- Central Section: Yearly Evolution Line Chart (Light-only premium card) -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] border-t-4 border-t-[#0d4d98] space-y-4">
            <div class="border-b border-slate-100 pb-2">
                <h3 class="text-sm font-bold text-slate-850 uppercase tracking-wider">Evolución Anual de Publicaciones</h3>
                <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Histórico temporal de producción científica aprobada</p>
            </div>
            
            @if(empty($metrics['yearly_evolution']))
                <div class="flex flex-col items-center justify-center p-12 text-center">
                    <div class="p-4 bg-slate-50 text-slate-400 rounded-full mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-400 italic">No hay suficientes datos históricos para graficar la evolución temporal.</p>
                </div>
            @else
                <div class="relative h-80 w-full" 
                     x-data="lineChart('yearlyEvolution', @js(array_column($metrics['yearly_evolution'], 'year')), @js(array_column($metrics['yearly_evolution'], 'total')), 'Trabajos Publicados')">
                    <canvas x-ref="canvas"></canvas>
                </div>
            @endif
        </div>

        <!-- Bottom Grid: Comparative Bar Charts (Alpine Tabs) & Rankings Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
            
            <!-- Left: Comparative Bar Charts with Alpine.js Tabs -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] border-t-4 border-t-[#0d4d98] flex flex-col space-y-4"
                 x-data="{ activeTab: 'program' }">
                
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-wrap gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-850 uppercase tracking-wider">Comparativa de Producción</h3>
                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Distribución cuantitativa de las obras</p>
                    </div>
                    
                    <!-- Alpine Tabs Selector -->
                    <div class="inline-flex rounded-lg shadow-sm bg-slate-50 p-1 border border-slate-200/40">
                        <button type="button" 
                                @click="activeTab = 'program'" 
                                :class="activeTab === 'program' ? 'bg-white text-[#0d4d98] font-bold shadow-sm' : 'text-slate-400 hover:text-slate-650'"
                                class="px-3 py-1 rounded-md text-[10px] uppercase font-bold tracking-wider transition">
                            Por Programa
                        </button>
                        <button type="button" 
                                @click="activeTab = 'line'" 
                                :class="activeTab === 'line' ? 'bg-white text-[#0d4d98] font-bold shadow-sm' : 'text-slate-400 hover:text-slate-650'"
                                class="px-3 py-1 rounded-md text-[10px] uppercase font-bold tracking-wider transition">
                            Por Línea
                        </button>
                    </div>
                </div>

                <!-- Tab content: Program Chart -->
                <div x-show="activeTab === 'program'" class="relative h-80 w-full"
                     x-data="barChart('byProgram', @js(array_column($metrics['by_program'], 'program')), @js(array_column($metrics['by_program'], 'total')), 'Producción por Programa')">
                    <canvas x-ref="canvas"></canvas>
                </div>

                <!-- Tab content: Research Line Chart -->
                <div x-show="activeTab === 'line'" class="relative h-80 w-full" style="display: none;"
                     x-data="barChart('byResearchLine', @js(array_column($metrics['by_research_line'], 'line')), @js(array_column($metrics['by_research_line'], 'total')), 'Producción por Línea')">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Right: Rankings Tables (Tutors and Research Lines) -->
            <div class="space-y-6">
                <!-- Top Tutors Table -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] border-t-4 border-t-[#F5B800] space-y-3">
                    <div class="border-b border-slate-100 pb-2">
                        <h3 class="text-sm font-bold text-slate-850 uppercase tracking-wider">Top Tutores</h3>
                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Docentes con mayor volumen de tutorías aprobadas</p>
                    </div>
                    
                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-bold text-slate-500 uppercase tracking-wider">Tutor Académico</th>
                                    <th class="px-4 py-2.5 text-right font-bold text-slate-500 uppercase tracking-wider w-24">Prods.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($metrics['top_tutors'] as $tutor)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-2.5 text-slate-800 font-semibold">{{ $tutor['tutor'] }}</td>
                                        <td class="px-4 py-2.5 text-slate-700 text-right font-bold font-mono">{{ $tutor['total'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-4 text-xs text-slate-400 text-center italic">No hay datos disponibles para tutores.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Research Lines Table -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)] border-t-4 border-t-[#F5B800] space-y-3">
                    <div class="border-b border-slate-100 pb-2">
                        <h3 class="text-sm font-bold text-slate-850 uppercase tracking-wider">Líneas de Mayor Impacto</h3>
                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Líneas de investigación con mayor nivel de publicación</p>
                    </div>
                    
                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-bold text-slate-500 uppercase tracking-wider">Línea de Investigación</th>
                                    <th class="px-4 py-2.5 text-right font-bold text-slate-500 uppercase tracking-wider w-24">Prods.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($metrics['top_research_lines'] as $line)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-2.5 text-slate-800 font-semibold">{{ $line['line'] }}</td>
                                        <td class="px-4 py-2.5 text-slate-700 text-right font-bold font-mono">{{ $line['total'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-4 text-xs text-slate-400 text-center italic">No hay datos disponibles para líneas.</td>
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
        <!-- Load Chart.js from CDN if not bundled, otherwise ensure globally available -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                // Configure global Chart.js defaults for Montserrat brand styling and consistent light aesthetics
                Chart.defaults.font.family = 'Montserrat';
                Chart.defaults.font.weight = '500';
                Chart.defaults.color = '#64748B'; // slate-500
                
                const commonOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0d4d98',
                            titleFont: { family: 'Montserrat', weight: '700', size: 12 },
                            bodyFont: { family: 'Montserrat', weight: '500', size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: false
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { weight: '600', size: 10 } }
                        },
                        y: {
                            border: { dash: [4, 4] },
                            grid: { color: '#F1F5F9' }, // slate-100
                            ticks: { font: { weight: '600', size: 10 } }
                        }
                    }
                };

                // Line Chart Alpine Component
                Alpine.data('lineChart', (name, labels, data, labelText) => ({
                    init() {
                        const ctx = this.$refs.canvas.getContext('2d');
                        
                        // Create vertical gradient for Azul UNIMAR fill
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, 'rgba(13, 77, 152, 0.15)');
                        gradient.addColorStop(1, 'rgba(13, 77, 152, 0.00)');

                        new Chart(this.$refs.canvas, {
                            type: 'line',
                            data: {
                                labels,
                                datasets: [{
                                    label: labelText,
                                    data,
                                    borderColor: '#0d4d98', // Azul UNIMAR
                                    borderWidth: 3,
                                    pointBackgroundColor: '#0d4d98',
                                    pointBorderColor: '#FFFFFF',
                                    pointBorderWidth: 2,
                                    pointRadius: 5,
                                    pointHoverRadius: 7,
                                    backgroundColor: gradient,
                                    fill: true,
                                    tension: 0.35
                                }]
                            },
                            options: {
                                ...commonOptions,
                                scales: {
                                    ...commonOptions.scales,
                                    y: {
                                        ...commonOptions.scales.y,
                                        beginAtZero: true,
                                        ticks: { precision: 0, font: { weight: '600', size: 10 } }
                                    }
                                }
                            }
                        });
                    }
                }));

                // Bar Chart Alpine Component
                Alpine.data('barChart', (name, labels, data, labelText) => ({
                    init() {
                        new Chart(this.$refs.canvas, {
                            type: 'bar',
                            data: {
                                labels,
                                datasets: [{
                                    label: labelText,
                                    data,
                                    backgroundColor: '#10B981', // Emerald primary
                                    hoverBackgroundColor: '#059669',
                                    borderRadius: 8,
                                    borderSkipped: false
                                }]
                            },
                            options: {
                                ...commonOptions,
                                scales: {
                                    ...commonOptions.scales,
                                    y: {
                                        ...commonOptions.scales.y,
                                        beginAtZero: true,
                                        ticks: { precision: 0, font: { weight: '600', size: 10 } }
                                    }
                                }
                            }
                        });
                    }
                }));
            });
        </script>
    @endPushOnce
</x-dashboard-layout>
