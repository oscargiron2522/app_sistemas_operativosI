<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    // Crea las dos cuentas de demostracion. Las contrasenas salen del .env o se generan al azar.
    public function run(): void
    {
        $cuentas = [
            ['name' => 'admin', 'usuario' => 'admin', 'rol' => 'admin', 'env' => 'ADMIN_PASSWORD'],
            ['name' => 'observador', 'usuario' => 'observador', 'rol' => 'observador', 'env' => 'OBSERVADOR_PASSWORD'],
        ];

        foreach ($cuentas as $c) {
            $clave = env($c['env']) ?: Str::random(12);
            User::updateOrCreate(
                ['usuario' => $c['usuario']],
                ['name' => $c['name'], 'rol' => $c['rol'], 'password' => $clave]
            );
            $this->command->info("Usuario: {$c['usuario']}  |  Contrasena: {$clave}  |  Rol: {$c['rol']}");
        }
    }
}
