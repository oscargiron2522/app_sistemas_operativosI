# SysMonitor Web (LinuxCore)

Aplicación Laravel que muestra los recursos reales del Linux donde se ejecuta
(procesos, CPU, memoria, discos, usuarios) leyendo /proc y comandos de una lista blanca.
Incluye simuladores de planificación de CPU y de interbloqueos.

Requisitos: PHP 8.2+ (extensiones sqlite3, mbstring, xml, posix), Composer, Linux.

    composer install
    cp .env.example .env
    php artisan key:generate
    touch database/database.sqlite
    php artisan migrate --seed     # muestra usuario/contraseña de admin y observador
    php artisan serve --host=0.0.0.0 --port=8000

ENLACE WEB:
https://www.sysmonitorwebumg.com/

ACCESOS DEFAULT (PRUEBA):
User: admin
Pass: admin
