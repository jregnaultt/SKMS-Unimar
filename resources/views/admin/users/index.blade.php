@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-6 max-w-8xl mx-auto pb-12" x-data="{
        openDrawer: false,
        selectedUser: {
            id: '',
            name: '',
            email: '',
            roles: []
        },
        editUser(user) {
            this.selectedUser = {
                id: user.id,
                name: user.name,
                email: user.email,
                roles: user.roles.map(r => r.name)
            };
            this.openDrawer = true;
        }
    }">

        <!-- Encabezado de la Página -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight font-sans">Gestión de Usuarios y Roles</h1>
                <p class="text-base text-slate-500 mt-1 font-medium">Administración de accesos y asignación de roles institucionales para estudiantes, tutores y jurados</p>
            </div>

            <!-- Buscador Rápido -->
            <div class="w-full md:w-96 shrink-0">
                <form action="{{ route('admin.users.index') }}" method="GET">
                    <div class="relative">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Buscar por nombre, email o cédula..."
                               class="block w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-base focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition duration-150 text-slate-700 placeholder-slate-400 font-medium" />
                        <button type="submit" class="absolute left-3 top-3.5 text-slate-400 hover:text-unimar-blue transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Alertas Flash del Sistema -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-sm text-emerald-800 transition duration-300">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold text-base">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm text-rose-800 transition duration-300">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold text-base">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Grid para acomodar la tabla y el drawer lateral -->
        <div class="relative flex flex-col lg:flex-row gap-4 items-start">

            <!-- Tabla de Usuarios (Ocupa el ancho completo o 2/3 si el drawer está abierto) -->
            <div id="admin-users-table-card" class="w-full transition-all duration-300 bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden"
                 :class="openDrawer ? 'lg:w-2/3' : 'w-full'">

                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-800 font-sans">Usuarios Registrados</h3>
                    <p class="text-base text-slate-500 mt-0.5 font-medium">Asigna y revoca roles institucionales para controlar el acceso a módulos específicos del sistema</p>
                </div>

                @if ($users->isEmpty())
                    <div class="py-16 text-center text-slate-400 border-t border-slate-100">
                        <svg class="mx-auto h-12 w-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <h4 class="text-base font-semibold text-slate-700">No se encontraron usuarios</h4>
                        <p class="text-base text-slate-500 mt-1">Intenta modificar los términos del buscador rápido de arriba.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[700px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-base font-bold uppercase tracking-wider text-slate-500">
                                    <th class="p-4 pl-6">Usuario / Correo</th>
                                    <th class="p-4">Cédula</th>
                                    <th class="p-4">Roles Asignados</th>
                                    <th class="p-4 pr-6 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-base text-slate-700">
                                @foreach ($users as $u)
                                    <tr class="hover:bg-slate-50/50 transition duration-150"
                                        :class="selectedUser.id == {{ $u->id }} ? 'bg-slate-50 border-l-4 border-unimar-blue' : ''">
                                        <td class="p-4 pl-6 whitespace-nowrap">
                                            <div class="flex items-center space-x-3">
                                                <!-- Avatar Placeholder -->
                                                <div class="w-9 h-9 rounded-full bg-unimar-blue/10 border border-unimar-blue/20 flex items-center justify-center text-unimar-blue font-bold text-base shrink-0">
                                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <span class="block font-bold text-slate-800 leading-tight">
                                                        {{ $u->name }}
                                                    </span>
                                                    <span class="block text-base text-slate-400 font-medium mt-0.5">
                                                        {{ $u->email }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 whitespace-nowrap text-slate-600 font-semibold font-mono">
                                            {{ $u->cedula ?? 'No registrada' }}
                                        </td>
                                        <td class="p-4">
                                            <div class="flex flex-wrap gap-1.5">
                                                @forelse($u->roles as $r)
                                                    @php
                                                        $roleColors = [
                                                            'Super Admin' => 'bg-red-50 text-red-800 border-red-200',
                                                            'Coordinador' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                                                            'Tutor' => 'bg-purple-50 text-purple-800 border-purple-200',
                                                            'Jurado' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                            'Estudiante' => 'bg-blue-50 text-unimar-blue border-blue-200',
                                                        ];
                                                        $badgeClass = $roleColors[$r->name] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                                    @endphp
                                                    <span class="px-2.5 py-0.5 inline-flex text-base font-bold rounded-xl border {{ $badgeClass }}">
                                                        {{ $r->name }}
                                                    </span>
                                                @empty
                                                    <span class="text-base text-slate-400 italic font-medium">Sin roles asignados</span>
                                                @endforelse
                                            </div>
                                                                          <td class="p-4 pr-6 whitespace-nowrap text-right text-base font-medium">
                                            <button type="button"
                                                    @click="editUser({
                                                         id: {{ $u->id }},
                                                         name: '{{ addslashes($u->name) }}',
                                                         email: '{{ addslashes($u->email) }}',
                                                         roles: {{ $u->roles->toJson() }}
                                                     })"
                                                    class="inline-flex items-center px-3.5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-xl text-base font-bold transition shadow-sm focus:outline-none h-11 cursor-pointer">
                                                Editar Roles
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($users->hasPages())
                        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                            {{ $users->appends(request()->query())->links() }}
                        </div>
                    @endif
                @endif
            </div>

            <!-- Panel Lateral Deslizable (Side-Drawer) -->
            <div id="admin-users-drawer-card" x-show="openDrawer"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full opacity-0"
                 x-transition:enter-end="translate-x-0 opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0 opacity-100"
                 x-transition:leave-end="translate-x-full opacity-0"
                 class="w-full lg:w-1/3 bg-white border border-slate-200 shadow-xl rounded-2xl overflow-hidden sticky top-6 shrink-0"
                 style="display: none;">

                <!-- Cabecera del Drawer -->
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800 font-sans">Detalles de Acceso</h3>
                    <button type="button" @click="openDrawer = false" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                        <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Formulario de Edición -->
                <form :action="'/admin/users/' + selectedUser.id" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="p-6 space-y-6">
                        <!-- Detalles del Usuario Seleccionado -->
                        <div class="flex items-center space-x-4 p-4 bg-slate-50 rounded-2xl border border-slate-200/60">
                            <div class="w-12 h-12 rounded-full bg-unimar-blue/10 border border-unimar-blue/20 flex items-center justify-center text-unimar-blue font-extrabold text-base shrink-0">
                                <span x-text="selectedUser.name ? selectedUser.name.substring(0, 2).toUpperCase() : ''"></span>
                            </div>
                            <div class="truncate">
                                <span class="block font-bold text-slate-800 text-base truncate" x-text="selectedUser.name"></span>
                                <span class="block text-base text-slate-550 font-bold truncate mt-0.5" x-text="selectedUser.email"></span>
                            </div>
                        </div>

                        <!-- Sección de Asignación de Roles -->
                        <div class="space-y-3">
                            <label class="block text-base font-bold text-slate-650 uppercase tracking-wider">Roles del Sistema</label>
                            <div class="space-y-3">

                                <!-- Coordinador -->
                                <label class="flex items-start p-3 bg-white border border-slate-200 rounded-xl hover:bg-slate-50/50 cursor-pointer transition">
                                    <input type="checkbox"
                                           name="roles[]"
                                           value="Coordinador"
                                           :checked="selectedUser.roles.includes('Coordinador')"
                                           class="mt-1 rounded border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4 cursor-pointer" />
                                    <div class="ml-3 text-base">
                                        <span class="block font-bold text-slate-700">Coordinador de Investigación</span>
                                        <span class="block text-base text-slate-550 font-semibold mt-0.5 leading-relaxed">Acceso completo al panel administrativo, reportes, OAI-PMH y control de cohortes académicas.</span>
                                    </div>
                                </label>

                                <!-- Tutor -->
                                <label class="flex items-start p-3 bg-white border border-slate-200 rounded-xl hover:bg-slate-50/50 cursor-pointer transition">
                                    <input type="checkbox"
                                           name="roles[]"
                                           value="Tutor"
                                           :checked="selectedUser.roles.includes('Tutor')"
                                           class="mt-1 rounded border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4 cursor-pointer" />
                                    <div class="ml-3 text-base">
                                        <span class="block font-bold text-slate-700">Tutor Académico</span>
                                        <span class="block text-base text-slate-550 font-semibold mt-0.5 leading-relaxed">Permite la revisión activa, carga de observaciones de tesis y aprobación en el flujo de trabajo.</span>
                                    </div>
                                </label>

                                <!-- Jurado -->
                                <label class="flex items-start p-3 bg-white border border-slate-200 rounded-xl hover:bg-slate-50/50 cursor-pointer transition">
                                    <input type="checkbox"
                                           name="roles[]"
                                           value="Jurado"
                                           :checked="selectedUser.roles.includes('Jurado')"
                                           class="mt-1 rounded border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4 cursor-pointer" />
                                    <div class="ml-3 text-base">
                                        <span class="block font-bold text-slate-700">Jurado Evaluador</span>
                                        <span class="block text-base text-slate-550 font-semibold mt-0.5 leading-relaxed">Permite la evaluación final de tesis asignadas y registro del veredicto académico de aprobación.</span>
                                    </div>
                                </label>

                                <!-- Estudiante -->
                                <label class="flex items-start p-3 bg-white border border-slate-200 rounded-xl hover:bg-slate-50/50 cursor-pointer transition">
                                    <input type="checkbox"
                                           name="roles[]"
                                           value="Estudiante"
                                           :checked="selectedUser.roles.includes('Estudiante')"
                                           class="mt-1 rounded border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4 cursor-pointer" />
                                    <div class="ml-3 text-base">
                                        <span class="block font-bold text-slate-700">Estudiante / Investigador</span>
                                        <span class="block text-base text-slate-550 font-semibold mt-0.5 leading-relaxed">Permite la carga de documentos de tesis, metadatos Dublin Core y seguimiento del propio progreso.</span>
                                    </div>
                                </label>

                            </div>
                        </div>
                    </div>

                    <!-- Pie del Drawer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end space-x-3">
                        <button type="button"
                                @click="openDrawer = false"
                                class="py-3 px-4 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold rounded-xl text-base uppercase tracking-wider transition focus:outline-none h-11 inline-flex items-center justify-center cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="py-3 px-5 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-base uppercase tracking-wider transition shadow-sm hover:shadow-md focus:outline-none h-11 inline-flex items-center justify-center cursor-pointer">
                            Guardar
                        </button>
                    </div>
                </form>

            </div>

        </div>

    </div>
</x-dashboard-layout>
