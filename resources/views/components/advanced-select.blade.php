@props([
    'name',
    'multiple' => false,
    'endpoint' => '',
    'options' => [],
    'selected' => [],
    'placeholder' => 'Seleccione una opción...',
    'optionsWatch' => '',
    'disabled' => 'false'
])

@php
    $normalizedOptions = collect($options)->map(function ($item) {
        if (is_array($item)) {
            return [
                'id' => $item['id'] ?? $item['value'] ?? '',
                'text' => $item['text'] ?? $item['label'] ?? $item['name'] ?? ''
            ];
        }
        if (is_object($item)) {
            return [
                'id' => $item->id ?? $item->getKey(),
                'text' => $item->name ?? $item->titulo ?? (string) $item
            ];
        }
        return ['id' => $item, 'text' => (string) $item];
    })->values()->all();

    $normalizedSelected = collect($selected)->map(function ($item) {
        if (is_array($item)) {
            return [
                'id' => $item['id'] ?? $item['value'] ?? '',
                'text' => $item['text'] ?? $item['label'] ?? $item['name'] ?? ''
            ];
        }
        if (is_object($item)) {
            return [
                'id' => $item->id ?? $item->getKey(),
                'text' => $item->name ?? $item->titulo ?? (string) $item
            ];
        }
        return ['id' => $item, 'text' => (string) $item];
    })->values()->all();
@endphp

<div 
    x-data="advancedSelect({
        multiple: @json($multiple),
        endpoint: '{{ $endpoint }}',
        options: @json($normalizedOptions),
        selected: @json($normalizedSelected),
        optionsWatch: '{{ $optionsWatch }}',
        disabled: {{ $disabled }}
    })"
    class="relative"
    @click.outside="open = false"
>
    <!-- Hidden inputs for form submission -->
    @if($multiple)
        <template x-for="item in selected" :key="item.id">
            <input type="hidden" name="{{ $name }}[]" :value="item.id">
        </template>
    @else
        <input type="hidden" name="{{ $name }}" :value="selected.length ? selected[0].id : ''">
    @endif

    <!-- Main Selection Trigger Input -->
    <div 
        @click="if (!disabled && !open) { open = true; $nextTick(() => $refs.searchInput.focus()) }"
        class="flex flex-wrap items-center gap-1.5 min-h-[44px] w-full rounded-xl border border-slate-200 bg-white text-slate-700 text-sm py-1.5 px-3 transition duration-150"
        :class="{ 
            'ring-2 ring-unimar-blue/50 border-unimar-blue': open && !disabled,
            'bg-slate-100 cursor-not-allowed opacity-60': disabled,
            'cursor-pointer': !disabled && !open
        }"
    >
        <!-- Selected Items Badges (Multiple Mode) -->
        <template x-if="multiple">
            <div class="flex flex-wrap items-center gap-1.5">
                <template x-for="item in selected" :key="item.id">
                    <span class="inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold py-1 px-2.5 rounded-lg border border-slate-200 transition-colors">
                        <span x-text="item.text"></span>
                        <button 
                            type="button" 
                            @click.stop="if (!disabled) { removeOption(item.id) }" 
                            class="text-slate-400 hover:text-slate-600 focus:outline-none"
                            :class="{ 'pointer-events-none': disabled }"
                        >
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </span>
                </template>
            </div>
        </template>

        <!-- Selected Item Text (Single Mode) -->
        <template x-if="!multiple && selected.length > 0 && !open">
            <span class="text-slate-800 font-medium" x-text="selected[0].text"></span>
        </template>

        <!-- Search Input -->
        <input 
            type="text" 
            x-ref="searchInput"
            x-model="search"
            :disabled="disabled"
            @click.stop
            @focus="if (!disabled) { open = true }"
            @keydown.down.prevent="highlightNext()"
            @keydown.up.prevent="highlightPrev()"
            @keydown.enter.prevent="selectHighlighted()"
            @keydown.escape="open = false"
            @keydown.backspace="if (search.length === 0 && multiple) { removeOption(selected[selected.length - 1]?.id) }"
            placeholder="{{ $placeholder }}"
            class="flex-1 min-w-[120px] bg-transparent border-0 p-0 text-sm text-slate-700 focus:ring-0 focus:outline-none placeholder-slate-400"
            :class="{ 
                'opacity-0': !multiple && selected.length > 0 && !open,
                'cursor-not-allowed': disabled 
            }"
        >

        <!-- Chevron / Indicator Arrow -->
        <div class="ml-auto pl-2 flex items-center text-slate-400" x-show="!disabled">
            <!-- Dropdown Arrow (Clickable Toggle) -->
            <button 
                type="button"
                @click.stop="open = !open; if (open) $nextTick(() => $refs.searchInput.focus())"
                class="focus:outline-none cursor-pointer"
            >
                <svg class="h-4 w-4 transition-transform duration-150" :class="{ 'rotate-180 text-unimar-blue': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Dropdown List -->
    <div 
        x-show="open && !disabled"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto"
        style="display: none;"
    >
        <ul class="py-1 text-sm text-slate-700">
            <!-- Options List -->
            <template x-for="(option, index) in options" :key="option.id">
                <li 
                    @click="selectOption(option)"
                    @mouseenter="highlightedIndex = index"
                    class="cursor-pointer select-none py-2 px-3 flex items-center justify-between"
                    :class="{ 
                        'bg-slate-50 text-unimar-blue font-semibold': highlightedIndex === index,
                        'text-slate-900': highlightedIndex !== index,
                        'bg-blue-50/50': selected.some(item => item.id === option.id)
                    }"
                >
                    <span x-text="option.text"></span>
                    <svg 
                        x-show="selected.some(item => item.id === option.id)"
                        class="h-4 w-4 text-unimar-blue" 
                        fill="none" 
                        viewBox="0 0 24 24" 
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </li>
            </template>

            <!-- No Results Found -->
            <template x-if="options.length === 0 && !loading">
                <li class="text-slate-400 py-3 px-4 text-center cursor-default select-none">
                    No se encontraron resultados
                </li>
            </template>

            <!-- Loading Skeleton (AJAX loading) -->
            <template x-if="loading && options.length === 0">
                <li class="text-slate-400 py-3 px-4 text-center cursor-default select-none flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-unimar-blue" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Buscando...</span>
                </li>
            </template>
        </ul>
    </div>
</div>
