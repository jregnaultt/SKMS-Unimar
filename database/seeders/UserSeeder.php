<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('Unimar2026!');

        $users = [
            // Estudiantes
            [
                'name' => 'Javier Regnault',
                'email' => 'jregnault.6759@unimar.edu.ve',
                'cedula' => 'V-20000001',
                'telefono' => '0412-0000001',
                'roles' => ['Estudiante'],
            ],
            [
                'name' => 'César Vethencourt',
                'email' => 'cvethencourt.4518@unimar.edu.ve',
                'cedula' => 'V-20000002',
                'telefono' => '0412-0000002',
                'roles' => ['Estudiante'],
            ],
            [
                'name' => 'José Ferreira',
                'email' => 'jferreira.5655@unimar.edu.ve',
                'cedula' => 'V-20000003',
                'telefono' => '0412-0000003',
                'roles' => ['Estudiante'],
            ],
            [
                'name' => 'Geyser Velásquez',
                'email' => 'gvelasquez.9312@unimar.edu.ve',
                'cedula' => 'V-20000004',
                'telefono' => '0412-0000004',
                'roles' => ['Estudiante'],
            ],

            // Tutores / Jurados
            [
                'name' => 'Oswald Marín',
                'email' => 'omarin.4205@unimar.edu.ve',
                'cedula' => 'V-10000001',
                'telefono' => '0414-0000001',
                'roles' => ['Tutor', 'Jurado'],
            ],
            [
                'name' => 'César Requena',
                'email' => 'crequena.4866@unimar.edu.ve',
                'cedula' => 'V-10000002',
                'telefono' => '0414-0000002',
                'roles' => ['Tutor', 'Jurado'],
            ],
            [
                'name' => 'Isabel Flores',
                'email' => 'iflores.7516@unimar.edu.ve',
                'cedula' => 'V-10000003',
                'telefono' => '0414-0000003',
                'roles' => ['Jurado'],
            ],
            [
                'name' => 'Jesús Rodríguez',
                'email' => 'jrodriguez.2980@unimar.edu.ve',
                'cedula' => 'V-10000004',
                'telefono' => '0414-0000004',
                'roles' => ['Jurado'],
            ],

            // Administrativos
            [
                'name' => 'Flavio Rosales',
                'email' => 'flavio.rosales@unimar.edu.ve',
                'cedula' => 'V-05000001',
                'telefono' => '0295-0000001',
                'roles' => ['Decano'],
            ],
            [
                'name' => 'Yemnel Torcat',
                'email' => 'ingenieria.investigacion.pasantias@unimar.edu.ve',
                'cedula' => 'V-05000002',
                'telefono' => '0295-0000002',
                'roles' => ['Coordinador'],
            ],
        ];

        foreach ($users as $userData) {
            $roles = $userData['roles'];
            unset($userData['roles']);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['password' => $defaultPassword])
            );

            $user->syncRoles($roles);
        }
    }
}
