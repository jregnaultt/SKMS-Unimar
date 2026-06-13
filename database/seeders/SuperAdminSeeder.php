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
        $god = User::updateOrCreate(
            ['email' => 'god@unimar.edu.ve'],
            [
                'name' => 'God',
                'password' => Hash::make('--god--'),
                'cedula' => '00000000',
                'telefono' => '0000-0000000',
            ]
        );

        $god->assignRole('Super Admin');
    }
}
