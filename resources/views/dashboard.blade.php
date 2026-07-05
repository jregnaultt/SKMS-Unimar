<x-dashboard-layout :roles="$roles" :activeRole="$activeRole">
    @switch($activeRole)
        @case('Estudiante')
            @include('dashboard.student', ['data' => $data])
            @break
        @case('Tutor')
        @case('Jurado')
            @include('dashboard.tutor', ['data' => $data])
            @break
        @case('Coordinador')
        @case('Decano')
            @include('dashboard.coordinator', ['data' => $data])
            @break
        @case('Super Admin')
            @include('dashboard.admin', ['data' => $data])
            @break
        @default
            @include('dashboard.student', ['data' => $data])
    @endswitch
</x-dashboard-layout>
