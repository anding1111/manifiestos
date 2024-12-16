<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Crear usuarios manualmente
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'), // Contraseña encriptada
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password123'), // Contraseña encriptada
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Usar un Factory (opcional, si necesitas múltiples usuarios)
        \App\Models\User::factory(10)->create();
    }
}
