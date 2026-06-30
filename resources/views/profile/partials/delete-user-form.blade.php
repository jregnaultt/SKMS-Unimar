<section class="space-y-6">
    <header class="mb-6">
        <h2 class="text-xl font-bold text-rose-800 tracking-tight">
            Eliminar Cuenta
        </h2>
        <p class="text-xs text-rose-600 mt-1">
            Una vez que se elimine tu cuenta, todos sus recursos y datos se borrarán de forma permanente. Por favor, descarga cualquier información que desees conservar antes de proceder.
        </p>
    </header>

    <div class="pt-2">
        <button type="button" 
                x-data="" 
                x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" 
                class="py-2.5 px-6 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition-all duration-150 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 text-sm">
            Eliminar mi cuenta definitivamente
        </button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-8 sm:p-10 space-y-6">
            @csrf
            @method('delete')

            <div>
                <h3 class="text-xl font-bold text-slate-800">
                    ¿Estás seguro de que deseas eliminar tu cuenta?
                </h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Esta acción no se puede deshacer. Por favor, introduce tu contraseña para confirmar que deseas eliminar de forma permanente tu cuenta de investigador y todos tus datos asociados.
                </p>
            </div>

            <div>
                <label for="delete_password" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Contraseña de Confirmación</label>
                <input id="delete_password" 
                       name="password" 
                       type="password" 
                       required 
                       placeholder="Introduce tu contraseña actual" 
                       class="block w-full md:w-3/4 rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-rose-500 focus:ring focus:ring-rose-500/10 transition-all duration-150" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1.5 text-xs text-rose-600" />
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <button type="button" 
                        x-on:click="$dispatch('close')" 
                        class="py-2.5 px-6 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl border border-slate-200 transition-all duration-150 text-sm focus:outline-none">
                    Cancelar
                </button>

                <button type="submit" 
                        class="py-2.5 px-6 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition-all duration-150 shadow-sm hover:shadow-md text-sm focus:outline-none">
                    Confirmar Eliminación
                </button>
            </div>
        </form>
    </x-modal>
</section>
