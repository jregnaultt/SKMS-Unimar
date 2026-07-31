@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-8 max-w-8xl mx-auto pb-12">

        <!-- Encabezado de la Página -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight font-sans">Centro de Configuración Académica</h1>
                <p class="text-base text-slate-550 font-bold uppercase tracking-wider mt-1">Gestión centralizada de programas de estudio, líneas de investigación y períodos de cohorte activa</p>
            </div>
        </div>

        <!-- Encabezado de Pestañas Compartidas -->
        <div id="admin-config-tabs" class="border-b border-slate-200 bg-white p-4 rounded-2xl shadow-sm border flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex space-x-2 border-b border-slate-100 w-full sm:w-auto">
                <a href="{{ route('admin.programs.index') }}"
                   class="py-3 px-4 text-base font-bold uppercase tracking-wider border-b-2 {{ Request::routeIs('admin.programs.*') ? 'border-unimar-gold text-unimar-blue' : 'border-transparent text-slate-600 hover:text-slate-800' }} transition duration-150">
                    Programas Académicos
                </a>
                <a href="{{ route('admin.lines.index') }}"
                   class="py-3 px-4 text-base font-bold uppercase tracking-wider border-b-2 {{ Request::routeIs('admin.lines.*') ? 'border-unimar-gold text-unimar-blue' : 'border-transparent text-slate-600 hover:text-slate-800' }} transition duration-150">
                    Líneas de Investigación
                </a>
                <a href="{{ route('admin.periods.index') }}"
                   class="py-3 px-4 text-base font-bold uppercase tracking-wider border-b-2 {{ Request::routeIs('admin.periods.*') ? 'border-unimar-gold text-unimar-blue' : 'border-transparent text-slate-600 hover:text-slate-800' }} transition duration-150">
                    Períodos Académicos
                </a>
            </div>

            <!-- Botón dinámico -->
            <a href="{{ route('admin.lines.create') }}" class="py-3 px-5 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-base uppercase tracking-wider transition shadow-sm hover:shadow-md shrink-0 inline-flex items-center h-11 cursor-pointer">
                <svg aria-hidden="true" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                Nueva Línea
            </a>
        </div>

        <!-- Alertas Flash del Sistema -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-sm text-emerald-800 transition duration-300">
                <div class="flex items-center">
                    <svg aria-hidden="true" class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold text-base">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm text-rose-800 transition duration-300">
                <div class="flex items-center">
                    <svg aria-hidden="true" class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold text-base">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Listado de Líneas -->
        <div id="admin-config-table-card" class="bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 font-sans">Listado de Líneas de Investigación</h3>
                <p class="text-base text-slate-550 mt-0.5 font-bold uppercase tracking-wider">Administra las líneas de investigación asociadas a cada programa académico</p>
            </div>

            @if ($lines->isEmpty())
                <div class="py-16 text-center text-slate-550 border-t border-slate-100">
                    <svg aria-hidden="true" class="mx-auto h-12 w-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <h4 class="text-base font-bold text-slate-700 uppercase tracking-wider">No hay líneas de investigación registradas</h4>
                    <p class="text-base text-slate-550 mt-1 font-bold">Comienza creando una línea de investigación utilizando el botón de arriba.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[650px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-base font-bold uppercase tracking-wider text-slate-650">
                                <th class="p-4 pl-6">Nombre de la Línea</th>
                                <th class="p-4">Programa Académico</th>
                                <th class="p-4">Estado</th>
                                <th class="p-4 pr-6 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-base text-slate-700">
                            @foreach ($lines as $line)
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="p-4 pl-6">
                                        <div class="font-bold text-slate-800">
                                            {{ $line->name }}
                                        </div>
                                        @if($line->description)
                                            <p class="text-base text-slate-550 font-bold line-clamp-1 mt-0.5" title="{{ $line->description }}">
                                                {{ $line->description }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="p-4">
                                        @if($line->academicProgram)
                                            <span class="px-3 py-1 inline-flex text-base font-bold rounded-xl bg-blue-50 text-unimar-blue border border-blue-100">
                                                {{ $line->academicProgram->name }} ({{ $line->academicProgram->code }})
                                            </span>
                                        @else
                                            <span class="text-base text-slate-550 font-bold italic">No asignado</span>
                                        @endif
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 inline-flex text-base leading-5 font-bold rounded-full {{ $line->is_active ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                                            {{ $line->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="p-4 pr-6 whitespace-nowrap text-right text-base font-medium">
                                        <div class="flex items-center justify-end space-x-3">
                                            <a href="{{ route('admin.lines.edit', $line) }}" class="px-3.5 py-2 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-lg text-base font-bold transition shadow-sm h-11 inline-flex items-center cursor-pointer">
                                                Editar
                                            </a>
                                            <form action="{{ route('admin.lines.destroy', $line) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta línea de investigación? Esta acción no se puede deshacer.')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-base font-bold transition border border-rose-200 shadow-sm h-11 inline-flex items-center cursor-pointer">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($lines->hasPages())
                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                        {{ $lines->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-dashboard-layout>
