# Menú Cafetería

Aplicación PHP para catálogo, pedidos, ventas e inventario.

## Inicio rápido

- Requisitos: PHP 8+, Apache con `mod_rewrite` y MySQL/MariaDB.
- Configura las credenciales en `app/config/database.php`.
- Consulta [la guía de instalación, operación y verificación](docs/commerce.md).
- En XAMPP, coloca el proyecto bajo `htdocs`, habilita `mod_rewrite` y `AllowOverride All`, e inicia Apache y MySQL. La raíz `.htaccess` conserva las rutas habituales, por ejemplo `http://localhost/Proyecto-MenuCafeteria/login.php`.

## Estructura

```text
app/                  Configuración y lógica compartida no pública
database/migrations/  Migraciones SQL
docs/                 Guías del proyecto
public/               Páginas y recursos servidos por Apache
  admin/              Administración
  assets/             CSS e imágenes
  tienda/             Catálogo y pedidos
scripts/              Herramientas de diagnóstico para CLI
```

El directorio `public/` es el único que debe exponerse en un despliegue de producción. En XAMPP local, `.htaccess` enruta las URLs existentes hacia `public/` y bloquea el acceso web a los directorios internos.