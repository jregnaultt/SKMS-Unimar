<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminEmail = env('SUPER_ADMIN_EMAIL');
        $superAdminPassword = env('SUPER_ADMIN_PASSWORD');

        if (! is_string($superAdminEmail) || $superAdminEmail === '') {
            throw new \RuntimeException('SUPER_ADMIN_EMAIL debe configurarse en el archivo .env');
        }

        if (! is_string($superAdminPassword) || $superAdminPassword === '') {
            throw new \RuntimeException('SUPER_ADMIN_PASSWORD debe configurarse en el archivo .env con un valor seguro');
        }

        $god = User::updateOrCreate(
            ['email' => $superAdminEmail],
            [
                'name' => 'God',
                'password' => Hash::make($superAdminPassword),
                'cedula' => '00000000',
                'telefono' => '0000-0000000',
            ]
        );

        $god->assignRole('Super Admin');
    }
}
