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
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logoIco.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ sidebarOpen: false }" class="dashboard-system-layout font-sans antialiased bg-unimar-matte text-unimar-dark min-h-screen flex relative overflow-x-hidden">

    <!-- Sidebar Fijo Izquierdo -->
    <x-sidebar :roles="$roles" :activeRole="$activeRole" />

    <!-- Área de Contenido Principal -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Navbar Superior Interno -->
        <header class="h-16 bg-unimar-matte/90 backdrop-blur-md border-b border-slate-200/50 flex items-center justify-between px-4 md:px-8 sticky top-0 z-10 transition-all duration-200">
            <div class="flex items-center space-x-3">
                <!-- Hamburger Menu Button (Mobile/Tablet) -->
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg hover:bg-slate-100 text-slate-550 hover:text-slate-700 transition focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/20 mr-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h2 class="text-base md:text-xl font-bold text-slate-800 tracking-tight whitespace-nowrap">
                    ¡Hola, {{ explode(' ', auth()->user()->name)[0] }}! 👋
                </h2>
                <span class="hidden md:inline text-slate-300">|</span>
                <p class="hidden md:block text-base text-slate-500 whitespace-nowrap">
                    {{ now()->translatedFormat('l, d \d\e F \d\e Y') }}
                </p>
            </div>

            <!-- Interacciones Navbar -->
            <div class="flex items-center space-x-4 md:space-x-6">
                <!-- Google Calendar Sync Status -->
                <div class="relative shrink-0">
                    @if(auth()->user()->google_refresh_token)
                        <span class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap" title="Sincronizado con Google Calendar">
                            <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Calendario Conectado
                        </span>
                    @else
                        <a href="{{ route('google.redirect') }}" 
                           class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-unimar-blue text-white hover:bg-unimar-blue/95 border border-transparent whitespace-nowrap shadow-sm hover:shadow transition-all cursor-pointer" 
                           title="Sincronizar tus entregas con tu Google Calendar">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Conectar Google Calendar
                        </a>
                    @endif
                </div>

                <!-- Selector de Rol Activo (si aplica) -->
                @if(isset($roles) && count($roles) > 1)
                    <form action="{{ route('dashboard.switch-role') }}" method="POST" class="flex items-center space-x-2">
                        @csrf
                        <label for="role-switcher" class="hidden sm:inline-block text-sm text-slate-500 font-semibold whitespace-nowrap">Perfil activo:</label>
                        <select id="role-switcher" name="role" onchange="this.form.submit()" class="bg-white border border-slate-200 rounded-lg text-[11px] md:text-sm font-bold text-slate-700 py-1 px-2.5 pr-8 focus:outline-none focus:ring-2 focus:ring-[#0d4d98] focus:border-[#0d4d98] transition-all cursor-pointer">
                            @foreach($roles as $r)
                                <option value="{{ $r }}" {{ $activeRole === $r ? 'selected' : '' }}>{{ $r }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <div class="flex items-center space-x-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                        <span class="text-[11px] md:text-sm font-semibold text-slate-600 bg-white border border-slate-200/60 px-2.5 py-1 rounded-lg whitespace-nowrap">
                            <span class="hidden sm:inline text-slate-400 font-normal">Rol:</span> {{ $activeRole ?? 'Estudiante' }}
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
                    <button @click="open = !open" aria-label="Abrir menú de notificaciones" class="relative p-2 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-800 rounded-xl border border-slate-200/60 transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-[#0d4d98]">
                        <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span x-show="unreadCount > 0" class="absolute -top-1.5 -right-1.5 bg-rose-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-white ring-1 ring-rose-500/20" x-text="unreadCount"></span>
                    </button>

                    <!-- Dropdown de Notificaciones -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2.5 w-80 bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden z-30" style="display: none;">
                        <div class="p-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                            <span class="text-sm font-bold text-slate-800">Notificaciones</span>
                            <button @click="markAllRead()" x-show="unreadCount > 0" class="text-xs text-[#0d4d98] hover:underline font-bold">Marcar todo como leído</button>
                        </div>
                        
                        <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
                            <template x-for="n in notifications" :key="n.id">
                                <div class="p-3.5 hover:bg-slate-50 transition duration-150 flex flex-col space-y-1 cursor-pointer" @click="markRead(n.id)">
                                    <div class="flex items-center justify-between">
                                        <!-- Tipo de alerta visual -->
                                        <span class="w-2 h-2 rounded-full" :class="n.read_at ? 'bg-slate-300' : 'bg-blue-500'"></span>
                                        <span class="text-[9px] text-slate-400 font-semibold" x-text="new Date(n.created_at).toLocaleDateString('es-ES', {month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'})"></span>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-800" x-text="JSON.parse(n.data).title || 'Actualización de obra'"></h4>
                                    <p class="text-[11px] text-slate-500 leading-tight" x-text="JSON.parse(n.data).message || 'Hay una nueva actividad en tu producción científica.'"></p>
                                </div>
                            </template>
                            <div x-show="notifications.length === 0" class="p-6 text-center text-sm text-slate-400">
                                No tienes notificaciones.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Área de Trabajo Principal -->
        <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-4 md:py-6">
            <!-- Alertas Flash del Sistema -->
            @if (session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-sm text-emerald-800 transition-all duration-300 animate-fade-in-down">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-semibold text-sm">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm text-rose-800 transition-all duration-300 animate-fade-in-down">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-semibold text-sm">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            {{ $slot }}
        </main>

        <!-- Footer Unificado -->
        <footer class="w-full py-6 text-center text-[11px] text-slate-400 bg-unimar-matte border-t border-slate-200/50 mt-auto">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between space-y-2 sm:space-y-0">
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

<x-google-calendar-tutorial />

</body>
</html>
