<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Subject::updateOrCreate(
            ['name' => 'SEMINARIO METODOLÓGICO DE INVESTIGACIÓN'],
            ['code' => 'SMI1004341']
        );

        Subject::updateOrCreate(
            ['name' => 'Trabajo de Investigación I'],
            ['code' => 'TRI1106341']
        );

        Subject::updateOrCreate(
            ['name' => 'Trabajo de Investigación II'],
            ['code' => 'TRI1206441']
        );
    }
}
