# SysMonitor Web (LinuxCore)

Aplicación web desarrollada con Laravel para consultar recursos del sistema Linux donde se ejecuta (procesos, CPU, memoria, discos y usuarios). También incluye simuladores de planificación de CPU e interbloqueos.

## Despliegue en Google Cloud

La aplicación está publicada en una máquina virtual de Google Cloud con Debian 13. Apache 2 sirve la aplicación Laravel desde el directorio `public`, y el dominio `sysmonitorwebumg.com`, registrado en GoDaddy, apunta a la dirección IP externa de la máquina virtual.

La conexion queda formada por estos componentes:

1. **Dominio y DNS:** en GoDaddy, el registro DNS del dominio apunta a la IP externa de la VM. Conviene reservar una IP externa estática en Google Cloud para que el destino no cambie. El nombre `www` debe tener también su propio registro DNS o un alias hacia el dominio.
2. **Red de Google Cloud:** las reglas de entrada de la VPC permiten el tráfico web por TCP en los puertos 80 (HTTP) y 443 (HTTPS). El acceso administrativo por SSH debe limitarse a las direcciones necesarias.
3. **Apache 2:** el VirtualHost usa como `DocumentRoot` la carpeta `public` de Laravel y permite las reglas de reescritura necesarias para las rutas de la aplicación. El ejemplo del repositorio está en [`deploy/sysmonitor.conf`](deploy/sysmonitor.conf); reemplaza `TU-DOMINIO` por el dominio configurado antes de instalarlo en Apache.
4. **HTTPS:** Certbot, cliente de Let's Encrypt basado en Python, obtiene e instala el certificado TLS en Apache. El certificado permite que el navegador se conecte por HTTPS y Certbot se encarga de su renovación automática.

### Configuracion resumida

1. En Google Cloud, crea la VM Debian 13 y reserva una IP externa estática. Permite el tráfico web HTTP y HTTPS en las reglas de firewall de la VPC.
2. En GoDaddy, crea o actualiza el registro DNS tipo `A` del dominio para que apunte a esa IP. Espera a que se propague el cambio antes de solicitar el certificado.
3. Instala Apache 2, PHP con las extensiones requeridas por Laravel, Composer y las dependencias de la aplicación. Copia el proyecto a `/var/www/sysmonitor` y prepara el entorno de producción (`.env`, clave de Laravel, permisos y base de datos), sin publicar secretos en Git.
4. Configura el VirtualHost de Apache con `DocumentRoot /var/www/sysmonitor/public`. Habilita `mod_rewrite` y el sitio, valida la configuración con `sudo apachectl configtest` y recarga Apache:

   ```bash
   sudo a2enmod rewrite
   sudo a2ensite sysmonitor.conf
   sudo apachectl configtest
   sudo systemctl reload apache2
   ```

5. Instala Certbot y su complemento para Apache. Solicita el certificado para los nombres de dominio que resuelvan a la VM. Por ejemplo:

   ```bash
   sudo apt update
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache -d www.sysmonitorwebumg.com
   sudo certbot renew --dry-run
   ```

   Agrega cada nombre adicional con otro argumento `-d` solo si también tiene DNS configurado. En Certbot, selecciona la opción de redirección de HTTP a HTTPS si se desea forzar el uso de HTTPS.

Al finalizar, la aplicacion esta disponible en [https://www.sysmonitorwebumg.com/](https://www.sysmonitorwebumg.com/).

> No se incluyen en este README la IP de la VM, claves privadas, contraseñas ni valores del archivo `.env`.

## Ejecucion local

Requisitos: PHP 8.2 o superior (extensiones `sqlite3`, `mbstring`, `xml` y `posix`), Composer y Linux.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```
credenciales

admin
admin
