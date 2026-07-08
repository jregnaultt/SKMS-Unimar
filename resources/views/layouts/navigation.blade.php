<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-11 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('catalog.index')" :active="request()->routeIs('catalog.index')">
                        {{ __('Catálogo') }}
                    </x-nav-link>
                    @if(Auth::user()->hasAnyRole(['Coordinador', 'Super Admin']))
                        <x-nav-link :href="route('admin.claims.index')" :active="request()->routeIs('admin.claims.index')">
                            {{ __('Reclamaciones') }}
                        </x-nav-link>
                        <x-nav-link :href="route('bibliometrics.index')" :active="request()->routeIs('bibliometrics.index')">
                            {{ __('Bibliometría') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <!-- Alpine.js Notification Bell Component -->
                <div x-data="{
                    open: false,
                    notifications: [],
                    unreadCount: 0,
                    async fetchNotifications() {
                        try {
                            let response = await axios.get('{{ route('notifications.index') }}');
                            this.notifications = response.data.notifications;
                            this.unreadCount = response.data.unreadCount;
                        } catch (e) {
                            console.error('Error al cargar notificaciones:', e);
                        }
                    },
                    async markAsRead(id) {
                        try {
                            await axios.post(`/notifications/${id}/read`);
                            await this.fetchNotifications();
                        } catch (e) {
                            console.error(e);
                        }
                    },
                    async markAllAsRead() {
                        try {
                            await axios.post('{{ route('notifications.read-all') }}');
                            await this.fetchNotifications();
                        } catch (e) {
                            console.error(e);
                        }
                    }
                }"
                x-init="fetchNotifications(); setInterval(() => fetchNotifications(), 30000)"
                @click.outside="open = false"
                class="relative me-3">
                    <!-- Botón Campana -->
                    <button @click="open = !open" class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 focus:outline-none transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        <span x-show="unreadCount > 0" x-text="unreadCount" class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white"></span>
                    </button>

                    <!-- Menú Desplegable -->
                    <div x-show="open" x-transition class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 py-1" style="display: none;">
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 px-4 py-2">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Notificaciones</span>
                            <button x-show="unreadCount > 0" @click="markAllAsRead()" class="text-sm text-blue-600 hover:underline">Marcar todo leído</button>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <template x-for="n in notifications" :key="n.id">
                                <div :class="n.read_at ? 'bg-white dark:bg-gray-800' : 'bg-blue-50/50 dark:bg-blue-900/10'" class="px-4 py-2.5 border-b border-gray-50 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200" x-text="n.data.title"></p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5" x-text="n.data.message"></p>
                                    <div class="flex items-center justify-between mt-1.5">
                                        <span class="text-xs text-gray-400" x-text="new Date(n.created_at).toLocaleDateString()"></span>
                                        <button x-show="!n.read_at" @click="markAsRead(n.id)" class="text-xs text-blue-600 hover:underline">Marcar como leída</button>
                                    </div>
                                </div>
                            </template>
                            <div x-show="notifications.length === 0" class="px-4 py-6 text-center text-sm text-gray-500">
                                No tienes notificaciones
                            </div>
                        </div>
                    </div>
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-base leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('catalog.index')" :active="request()->routeIs('catalog.index')">
                {{ __('Catálogo') }}
            </x-responsive-nav-link>
            @if(Auth::user()->hasAnyRole(['Coordinador', 'Super Admin']))
                <x-responsive-nav-link :href="route('admin.claims.index')" :active="request()->routeIs('admin.claims.index')">
                    {{ __('Reclamaciones') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('bibliometrics.index')" :active="request()->routeIs('bibliometrics.index')">
                    {{ __('Bibliometría') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-base text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
