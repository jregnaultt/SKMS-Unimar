<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SuperAdminSeeder::class,
            UserSeeder::class,
            SubjectSeeder::class,
        ]);

        // Seed Academic Programs
        $sistemas = AcademicProgram::firstOrCreate(
            ['code' => 'ING-SIS'],
            ['name' => 'Ingeniería de Sistemas', 'description' => 'Programa de Ingeniería de Sistemas', 'is_active' => true]
        );

        $industrial = AcademicProgram::firstOrCreate(
            ['code' => 'ING-IND'],
            ['name' => 'Ingeniería Industrial', 'description' => 'Programa de Ingeniería Industrial', 'is_active' => true]
        );

        $naval = AcademicProgram::firstOrCreate(
            ['code' => 'TEC-NAV'],
            ['name' => 'Tecnología Naval', 'description' => 'Programa de Tecnología Naval', 'is_active' => true]
        );

        // Seed Research Lines
        ResearchLine::firstOrCreate(
            ['academic_program_id' => $sistemas->id, 'name' => 'Inteligencia Artificial'],
            ['is_active' => true, 'description' => 'Línea de investigación en Inteligencia Artificial']
        );
        ResearchLine::firstOrCreate(
            ['academic_program_id' => $sistemas->id, 'name' => 'Desarrollo de Software'],
            ['is_active' => true, 'description' => 'Línea de investigación en Desarrollo de Software']
        );

        ResearchLine::firstOrCreate(
            ['academic_program_id' => $industrial->id, 'name' => 'Optimización de Procesos'],
            ['is_active' => true, 'description' => 'Línea de investigación en Optimización de Procesos']
        );

        ResearchLine::firstOrCreate(
            ['academic_program_id' => $naval->id, 'name' => 'Sistemas de Propulsión y Estructuras Navales'],
            ['is_active' => true, 'description' => 'Línea de investigación en Sistemas Navales']
        );

        ResearchLine::firstOrCreate(
            ['academic_program_id' => $naval->id, 'name' => 'Diseño e Hidrodinámica Naval'],
            ['is_active' => true, 'description' => 'Línea de investigación en Diseño de Buques']
        );

        // Seed Production Types
        ProductionType::firstOrCreate(
            ['name' => 'Tesis de Grado'],
            ['description' => 'Trabajo especial para optar al título de grado']
        );
        ProductionType::firstOrCreate(
            ['name' => 'Trabajo de Investigación'],
            ['description' => 'Artículo o publicación científica libre']
        );

        // Seed Academic Periods
        AcademicPeriod::firstOrCreate(
            ['name' => '2026-I'],
            ['start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'is_active' => true]
        );
        AcademicPeriod::firstOrCreate(
            ['name' => '2026-II'],
            ['start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'is_active' => true]
        );
    }
}
