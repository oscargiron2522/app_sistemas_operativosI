<?php
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

function navItems() {
    return [
        'dashboard' => ['Dashboard', 'fa-house'],
        'procesos' => ['Procesos', 'fa-file-lines'],
        'cpu' => ['CPU y Memoria', 'fa-microchip'],
        'planificacion' => ['Planificación', 'fa-calendar-days'],
        'interbloqueos' => ['Interbloqueos', 'fa-diagram-project'],
        'almacenamiento' => ['Almacenamiento', 'fa-database'],
        'usuarios' => ['Usuarios y Grupos', 'fa-users'],
        'terminal' => ['Terminal', 'fa-terminal'],
        'reportes' => ['Reportes', 'fa-file-lines']
    ];
}

function pageTitle($page) {
    $titles = [
        'dashboard' => ['Dashboard', 'Resumen general del sistema en tiempo real'],
        'procesos' => ['Procesos', 'Listado y administración de procesos del sistema'],
        'cpu' => ['CPU y Memoria', 'Monitoreo en tiempo real del uso de CPU, memoria y carga del sistema.'],
        'planificacion' => ['Planificación de CPU', 'Simulador de algoritmos de planificación de procesos'],
        'interbloqueos' => ['Interbloqueos', 'Simulador del algoritmo del banquero y detección de interbloqueos'],
        'almacenamiento' => ['Almacenamiento', 'Información de discos, particiones y sistemas de archivos'],
        'usuarios' => ['Usuarios y Grupos', 'Administración de usuarios y grupos del sistema'],
        'terminal' => ['Terminal Web', 'Ejecuta comandos en el sistema de forma segura'],
        'reportes' => ['Reportes', 'Consulta y genera reportes del sistema operativo']
    ];
    return $titles[$page] ?? $titles['dashboard'];
}
