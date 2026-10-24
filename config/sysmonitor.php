<?php

return [
    // Directorios permitidos para el explorador de solo lectura (M5)
    'rutas_permitidas' => array_values(array_filter(array_map('trim', explode(',', env('SYSMON_RUTAS', '/var/log,/etc,/home,/tmp,/var/www,/opt'))))),

    // Maximo de procesos de prueba vivos al mismo tiempo (M1)
    'max_pruebas' => 10,
];
