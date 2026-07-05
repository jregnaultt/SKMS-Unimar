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
        $superAdminEmail = env('SUPER_ADMIN_EMAIL', 'god@unimar.edu.ve');
        $superAdminPassword = env('SUPER_ADMIN_PASSWORD', '--god--');

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
