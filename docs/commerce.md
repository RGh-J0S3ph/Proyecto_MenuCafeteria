# Menú Cafetería: comercio, ventas e inventario

## Requisitos

- PHP 8 o posterior con PDO MySQL habilitado.
- MySQL/MariaDB, como el incluido en XAMPP.
- La base `proyecto_php` y la tabla actual `usuarios` con `id`, `nombre`, `email` y `password`.

La conexión está en `app/config/database.php`. Ajusta allí host, base de datos y credenciales para tu entorno.

## Instalación de la base

1. Haz una copia de seguridad de `proyecto_php`.
2. En phpMyAdmin, selecciona esa base e importa `database/migrations/001_comercio.sql`. El archivo es una migración de una sola ejecución: añade el rol y crea las tablas nuevas; no borra usuarios ni datos existentes.
3. Promueve una cuenta de confianza a administrador desde la pestaña SQL, sustituyendo el correo:

   ```sql
   UPDATE usuarios SET rol = 'admin' WHERE email = 'admin@cafeteria.local';
   ```

4. Inicia sesión con esa cuenta. Los usuarios ya existentes y los nuevos registros quedan como `cliente` por defecto. El formulario público nunca acepta un rol enviado por el navegador.
5. Con Apache y MySQL iniciados en XAMPP, abre `http://localhost/Proyecto-MenuCafeteria/login.php`.

## Áreas

- `public/tienda/index.php`: catálogo, carrito en sesión, confirmación del pedido e historial del cliente.
- `public/admin/index.php`: accesos al panel administrativo.
- `public/admin/usuarios.php`: listado y activación/desactivación de cuentas cliente; las cuentas admin no se modifican desde aquí.
- `public/admin/productos.php`: categorías y productos (alta, edición y activación/desactivación).
- `public/admin/pedidos.php`: consulta de ventas y cancelación con devolución al inventario.
- `public/admin/compras.php`: alta de proveedores y recepción de compras de abastecimiento.

El acceso administrativo se valida en el servidor mediante `app/includes/auth.php`; no depende de ocultar botones. Los formularios que modifican datos validan token CSRF.

## Inventario y pedidos

Al confirmar un pedido se comprueba el stock dentro de una transacción con bloqueo de filas. Se guardan los precios de ese momento en `detalle_pedido`, se descuenta inventario y se registra un movimiento de tipo `venta`. El administrador puede marcar el pedido como completado o cancelar un pedido pendiente; la cancelación restituye unidades y anota un movimiento `ajuste`.

Cada compra de proveedor aumenta existencias y registra su costo en `detalle_compra`. El inventario manual usa ajustes con cantidad firmada: positiva para entrada y negativa para salida. `productos.stock` es el saldo actual y `movimientos_inventario` conserva la trazabilidad.

El checkout registra pedidos y stock; no integra pasarela de pago, facturación, impuestos, envío ni reservas de stock. Antes de vender en producción, deben definirse esos procesos y las reglas legales/fiscales aplicables.

## Verificación rápida

1. Importa la migración y promueve un administrador.
2. Registra una cuenta cliente y confirma que no pueda abrir `admin/`.
3. Desactiva y vuelve a activar la cuenta desde administración; confirma que una cuenta desactivada no pueda iniciar sesión.
4. Crea categoría y producto desde administración, luego registra un proveedor y recibe stock mediante una compra.
5. Entra como cliente, realiza un pedido y verifica el movimiento `venta`.
6. Cancela el pedido desde administración y comprueba que el saldo se reponga una sola vez.
