<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="user-id" content="{{ auth()->id() }}">
    @endauth

    <title>{{ config('app.name', 'SKMS Unimar') }} - Panel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-unimar-matte text-unimar-dark min-h-screen flex">

    <!-- Sidebar Fijo Izquierdo -->
    <aside class="w-64 bg-[#0d4d98] text-white flex flex-col justify-between shrink-0 min-h-screen sticky top-0 shadow-xl z-20">
        <!-- Sidebar Header -->
        <div class="p-6 border-b border-white/10">
            <div class="flex items-center space-x-3">
                <!-- Icono/Logo minimalista -->
                <div class="p-2 bg-white/10 rounded-lg border border-white/20 text-[#F5B800]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="font-extrabold text-lg tracking-tight text-white leading-tight">SKMS</h1>
                    <p class="text-[10px] text-white/60 tracking-wider font-semibold uppercase">UNIMAR</p>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-[10px] bg-[#F5B800]/20 text-[#F5B800] border border-[#F5B800]/30 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                    Decanato de Ingeniería
                </span>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-2">
            <!-- Dashboard (Active by default here) -->
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-150 {{ request()->routeIs('dashboard') ? 'bg-[#F5B800] text-slate-900 font-bold shadow-md' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path>
                </svg>
                <span class="text-sm">Cabina de Control</span>
            </a>

            <!-- Catálogo -->
            <a href="{{ route('catalog.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-150 {{ request()->routeIs('catalog.*') ? 'bg-[#F5B800] text-slate-900 font-bold shadow-md' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span class="text-sm">Catálogo</span>
            </a>

            <!-- Bibliometría -->
            <a href="{{ route('bibliometrics.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-150 {{ request()->routeIs('bibliometrics.*') ? 'bg-[#F5B800] text-slate-900 font-bold shadow-md' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span class="text-sm">Bibliometría</span>
            </a>

            <!-- Perfil -->
            <a href="{{ route('profile.edit') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-150 {{ request()->routeIs('profile.*') ? 'bg-[#F5B800] text-slate-900 font-bold shadow-md' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="text-sm">Mi Perfil</span>
            </a>

            <!-- Coordinator Admin Panel link -->
            @if(auth()->user()->hasRole(['Coordinador', 'Super Admin']))
                <div class="pt-4 pb-2">
                    <p class="px-4 text-[10px] text-white/40 font-bold uppercase tracking-wider">Gestión</p>
                </div>
                <div x-data="{ adminOpen: {{ request()->routeIs('admin.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <!-- Toggle Button -->
                    <button @click="adminOpen = !adminOpen" 
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-150 {{ request()->routeIs('admin.*') ? 'bg-[#F5B800]/15 text-[#F5B800] font-bold border border-[#F5B800]/30' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="text-sm">Panel Admin</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200 shrink-0" 
                             :class="adminOpen ? 'rotate-180' : ''" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Sub-menu Links -->
                    <div x-show="adminOpen" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="transform opacity-0 -translate-y-2 scale-95"
                         x-transition:enter-end="transform opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="transform opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="transform opacity-0 -translate-y-2 scale-95"
                         class="pl-6 py-1.5 space-y-1 bg-black/10 rounded-xl"
                         style="display: none;">
                        
                        <a href="{{ route('admin.programs.index') }}" 
                           class="flex items-center space-x-2.5 px-4 py-2 rounded-lg text-xs font-semibold transition {{ Request::routeIs('admin.programs.*') || Request::routeIs('admin.lines.*') || Request::routeIs('admin.periods.*') ? 'text-[#F5B800] bg-white/5 font-bold' : 'text-white/75 hover:text-white hover:bg-white/5' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            <span>Config. Académica</span>
                        </a>

                        <a href="{{ route('admin.users.index') }}" 
                           class="flex items-center space-x-2.5 px-4 py-2 rounded-lg text-xs font-semibold transition {{ Request::routeIs('admin.users.*') ? 'text-[#F5B800] bg-white/5 font-bold' : 'text-white/75 hover:text-white hover:bg-white/5' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            <span>Usuarios y Roles</span>
                        </a>

                        <a href="{{ route('admin.claims.index') }}" 
                           class="flex items-center space-x-2.5 px-4 py-2 rounded-lg text-xs font-semibold transition {{ Request::routeIs('admin.claims.*') ? 'text-[#F5B800] bg-white/5 font-bold' : 'text-white/75 hover:text-white hover:bg-white/5' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            <span>Reclamaciones</span>
                        </a>

                        <a href="{{ route('admin.audit-logs.index') }}" 
                           class="flex items-center space-x-2.5 px-4 py-2 rounded-lg text-xs font-semibold transition {{ Request::routeIs('admin.audit-logs.*') ? 'text-[#F5B800] bg-white/5 font-bold' : 'text-white/75 hover:text-white hover:bg-white/5' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            <span>Bitácora de Auditoría</span>
                        </a>

                        <a href="{{ route('admin.reports.index') }}" 
                           class="flex items-center space-x-2.5 px-4 py-2 rounded-lg text-xs font-semibold transition {{ Request::routeIs('admin.reports.*') ? 'text-[#F5B800] bg-white/5 font-bold' : 'text-white/75 hover:text-white hover:bg-white/5' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            <span>Reportes Inst.</span>
                        </a>
                    </div>
                </div>
            @endif
        </nav>

        <!-- Sidebar Footer / User Info -->
        <div class="p-4 border-t border-white/10 bg-black/10">
            <div class="flex items-center space-x-3 mb-3">
                <div class="w-9 h-9 rounded-full bg-[#F5B800] text-slate-900 font-bold flex items-center justify-center text-sm shadow-inner shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="truncate">
                    <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-white/50 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center space-x-2 px-4 py-2 bg-white/5 hover:bg-rose-500/20 hover:text-rose-300 border border-white/10 hover:border-rose-500/30 rounded-xl text-xs font-semibold transition-all duration-150">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Cerrar Sesión</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Área de Contenido Principal -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Navbar Superior Interno -->
        <header class="h-16 bg-unimar-matte/90 backdrop-blur-md border-b border-slate-200/50 flex items-center justify-between px-8 sticky top-0 z-10 transition-all duration-200">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-bold text-slate-800 tracking-tight">
                    ¡Hola, {{ explode(' ', auth()->user()->name)[0] }}! 👋
                </h2>
                <span class="text-slate-300">|</span>
                <p class="text-xs text-slate-500">
                    {{ now()->translatedFormat('l, d \d\e F \d\e Y') }}
                </p>
            </div>

            <!-- Interacciones Navbar -->
            <div class="flex items-center space-x-6">
                <!-- Selector de Rol Activo (si aplica) -->
                @if(isset($roles) && count($roles) > 1)
                    <form action="{{ route('dashboard.switch-role') }}" method="POST" class="flex items-center space-x-2">
                        @csrf
                        <label for="role-switcher" class="text-xs text-slate-500 font-semibold">Perfil activo:</label>
                        <select id="role-switcher" name="role" onchange="this.form.submit()" class="bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 py-1 px-2.5 pr-8 focus:outline-none focus:ring-2 focus:ring-[#0d4d98] focus:border-[#0d4d98] transition-all cursor-pointer">
                            @foreach($roles as $r)
                                <option value="{{ $r }}" {{ $activeRole === $r ? 'selected' : '' }}>{{ $r }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <div class="flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-semibold text-slate-600 bg-white border border-slate-200/60 px-2.5 py-1 rounded-lg">
                            Rol: {{ $activeRole ?? 'Estudiante' }}
                        </span>
                    </div>
                @endif

                <!-- Campana de Notificaciones (Alpine.js) -->
                <div x-data="{
                    open: false,
                    notifications: [],
                    unreadCount: 0,
                    async fetchNotifications() {
                        try {
                            let response = await fetch('{{ route('notifications.index') }}');
                            let data = await response.json();
                            this.notifications = data.notifications;
                            this.unreadCount = data.unreadCount;
                        } catch (e) {
                            console.error('Error fetching notifications:', e);
                        }
                    },
                    async markRead(id) {
                        try {
                            await fetch(`/notifications/${id}/read`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                }
                            });
                            this.fetchNotifications();
                        } catch (e) {
                            console.error('Error marking notification as read:', e);
                        }
                    },
                    async markAllRead() {
                        try {
                            await fetch('{{ route('notifications.read-all') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                }
                            });
                            this.fetchNotifications();
                        } catch (e) {
                            console.error('Error marking all notifications as read:', e);
                        }
                    }
                }" x-init="fetchNotifications(); setInterval(() => fetchNotifications(), 30000)" @click.outside="open = false" class="relative">
                    <button @click="open = !open" class="relative p-2 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-800 rounded-xl border border-slate-200/60 transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-[#0d4d98]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span x-show="unreadCount > 0" class="absolute -top-1.5 -right-1.5 bg-rose-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-white ring-1 ring-rose-500/20" x-text="unreadCount"></span>
                    </button>

                    <!-- Dropdown de Notificaciones -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2.5 w-80 bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden z-30" style="display: none;">
                        <div class="p-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800">Notificaciones</span>
                            <button @click="markAllRead()" x-show="unreadCount > 0" class="text-[10px] text-[#0d4d98] hover:underline font-bold">Marcar todo como leído</button>
                        </div>
                        
                        <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
                            <template x-for="n in notifications" :key="n.id">
                                <div class="p-3.5 hover:bg-slate-50 transition duration-150 flex flex-col space-y-1 cursor-pointer" @click="markRead(n.id)">
                                    <div class="flex items-center justify-between">
                                        <!-- Tipo de alerta visual -->
                                        <span class="w-2 h-2 rounded-full" :class="n.read_at ? 'bg-slate-300' : 'bg-blue-500'"></span>
                                        <span class="text-[9px] text-slate-400 font-semibold" x-text="new Date(n.created_at).toLocaleDateString('es-ES', {month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'})"></span>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-800" x-text="JSON.parse(n.data).title || 'Actualización de obra'"></h4>
                                    <p class="text-[11px] text-slate-500 leading-tight" x-text="JSON.parse(n.data).message || 'Hay una nueva actividad en tu producción científica.'"></p>
                                </div>
                            </template>
                            <div x-show="notifications.length === 0" class="p-6 text-center text-xs text-slate-400">
                                No tienes notificaciones.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Área de Trabajo Principal -->
        <main class="flex-1 w-full max-w-6xl mx-auto px-6 py-8">
            <!-- Alertas Flash del Sistema -->
            @if (session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-sm text-emerald-800 transition-all duration-300 animate-fade-in-down">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-semibold text-xs">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm text-rose-800 transition-all duration-300 animate-fade-in-down">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-semibold text-xs">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            {{ $slot }}
        </main>

        <!-- Footer Unificado -->
        <footer class="w-full py-6 text-center text-[11px] text-slate-400 bg-unimar-matte border-t border-slate-200/50 mt-auto">
            <div class="max-w-6xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between space-y-2 sm:space-y-0">
                <p>&copy; {{ date('Y') }} Decanato de Ingeniería y Afines - Universidad de Margarita. Todos los derechos reservados.</p>
                <div class="flex space-x-4">
                    <a href="https://unimar.edu.ve" target="_blank" class="hover:text-slate-600 transition">Portal UNIMAR</a>
                    <span>•</span>
                    <span class="font-semibold text-[#0d4d98]">SKMS v2.0</span>
                </div>
            </div>
        </footer>
    </div>
</div>

</body>
</html>
