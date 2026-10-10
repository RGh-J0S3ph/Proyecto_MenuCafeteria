# Menú Cafetería: comercio, ventas e inventario

## Requisitos

- PHP 8 o posterior con PDO MySQL habilitado.
- MySQL/MariaDB, como el incluido en XAMPP.
- La base `proyecto_php` y la tabla actual `usuarios` con `id`, `nombre`, `email` y `password`.

La conexión está en `app/config/database.php`. Ajusta allí host, base de datos y credenciales para tu entorno.

## Instalación de la base

1. Haz una copia de seguridad de `proyecto_php`.
2. En phpMyAdmin, selecciona esa base e importa `database/migrations/001_comercio.sql`. El archivo es una migración de una sola ejecución: añade el rol y crea las tablas nuevas; no borra usuarios ni datos existentes.
3. Importa también `database/migrations/002_tipo_producto.sql`. Los productos existentes quedan clasificados inicialmente como bebidas; cambia a refrigerio los que corresponda desde la gestión de productos.
4. Promueve una cuenta de confianza a administrador desde la pestaña SQL, sustituyendo el correo:

   ```sql
   UPDATE usuarios SET rol = 'admin' WHERE email = 'admin@cafeteria.local';
   ```

5. Inicia sesión con esa cuenta. Los usuarios ya existentes y los nuevos registros quedan como `cliente` por defecto. El formulario público nunca acepta un rol enviado por el navegador.
6. Con Apache y MySQL iniciados en XAMPP, abre `http://localhost/Proyecto-MenuCafeteria/login.php`.

## Áreas

- `public/tienda/index.php`: menú de bebidas y refrigerios con búsqueda y filtro, carrito en sesión, confirmación del pedido e historial del cliente.
- `public/admin/index.php`: accesos al panel administrativo.
- `public/admin/usuarios.php`: listado y activación/desactivación de cuentas cliente; las cuentas admin no se modifican desde aquí.
- `public/admin/productos.php`: categorías y productos tipo bebida o refrigerio (alta, edición y activación/desactivación); los cambios se reflejan en el menú público.
- `public/admin/pedidos.php`: consulta de ventas y cancelación con devolución al inventario.
- `public/admin/compras.php`: alta de proveedores y recepción de compras de abastecimiento.

El acceso administrativo se valida en el servidor mediante `app/includes/auth.php`; no depende de ocultar botones. Los formularios que modifican datos validan token CSRF.

## Inventario y pedidos

El inventario se limita a bebidas y refrigerios; los platillos quedan fuera del catálogo y del control de existencias. Al confirmar un pedido se comprueba el stock dentro de una transacción con bloqueo de filas. Se guardan los precios de ese momento en `detalle_pedido`, se descuenta inventario y se registra un movimiento de tipo `venta`. El administrador puede marcar el pedido como completado o cancelar un pedido pendiente; la cancelación restituye unidades y anota un movimiento `ajuste`.

Cada compra de proveedor aumenta existencias y registra su costo en `detalle_compra`. El inventario manual usa ajustes con cantidad firmada: positiva para entrada y negativa para salida. `productos.stock` es el saldo actual y `movimientos_inventario` conserva la trazabilidad.

El checkout registra pedidos y stock; no integra pasarela de pago, facturación, impuestos, envío ni reservas de stock. Antes de vender en producción, deben definirse esos procesos y las reglas legales/fiscales aplicables.

## Verificación rápida

1. Importa las migraciones 001 y 002, y promueve un administrador.
2. Registra una cuenta cliente y confirma que no pueda abrir `admin/`.
3. Desactiva y vuelve a activar la cuenta desde administración; confirma que una cuenta desactivada no pueda iniciar sesión.
4. Crea una categoría y productos de ambos tipos desde administración; verifica que no haya opción para registrar platillos. Edita un producto y confirma que aparece actualizado en el menú del cliente.
5. En el menú, prueba la búsqueda y los filtros por bebida/refrigerio. Luego registra un proveedor y recibe stock mediante una compra.
6. Entra como cliente, realiza un pedido y verifica el movimiento `venta`.
7. Cancela el pedido desde administración y comprueba que el saldo se reponga una sola vez.
