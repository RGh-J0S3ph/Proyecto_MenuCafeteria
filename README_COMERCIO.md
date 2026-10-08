# Menú Cafetería: comercio, ventas e inventario

## Requisitos

- PHP 8 o posterior con PDO MySQL habilitado.
- MySQL/MariaDB, como el incluido en XAMPP.
- La base `proyecto_php` y la tabla actual `usuarios` con `id`, `nombre`, `email` y `password`.

La conexión existente está en `config/database.php`. Ajusta allí host, base de datos y credenciales para tu entorno.

## Instalación de la base

1. Haz una copia de seguridad de `proyecto_php`.
2. En phpMyAdmin, selecciona esa base e importa `database/001_comercio.sql`. El archivo es una migración de una sola ejecución: añade el rol y crea las tablas nuevas; no borra usuarios ni datos existentes.
3. Promueve una cuenta de confianza a administrador desde la pestaña SQL, sustituyendo el correo:

   ```sql
   UPDATE usuarios SET rol = 'admin' WHERE email = 'admin@cafeteria.local';
   ```

4. Inicia sesión con esa cuenta. Los usuarios ya existentes y los nuevos registros quedan como `cliente` por defecto. El formulario público nunca acepta un rol enviado por el navegador.

## Áreas

- `tienda/index.php`: catálogo, carrito en sesión, confirmación del pedido e historial del cliente.
- `admin/index.php`: accesos al panel administrativo.
- `admin/productos.php`: categorías y productos (alta, edición y activación/desactivación).
- `admin/pedidos.php`: consulta de ventas y cancelación con devolución al inventario.
- `admin/compras.php`: alta de proveedores y recepción de compras de abastecimiento.

El acceso administrativo se valida en el servidor mediante `includes/auth.php`; no depende de ocultar botones. Los formularios que modifican datos validan token CSRF.

## Inventario y pedidos

Al confirmar un pedido se comprueba el stock dentro de una transacción con bloqueo de filas. Se guardan los precios de ese momento en `detalle_pedido`, se descuenta inventario y se registra un movimiento de tipo `venta`. Si el administrador cancela un pedido pendiente, las unidades se restituyen y se anota un movimiento `ajuste`.

Cada compra de proveedor aumenta existencias y registra su costo en `detalle_compra`. El inventario manual usa ajustes con cantidad firmada: positiva para entrada y negativa para salida. `productos.stock` es el saldo actual y `movimientos_inventario` conserva la trazabilidad.

El checkout registra pedidos y stock; no integra pasarela de pago, facturación, impuestos, envío ni reservas de stock. Antes de vender en producción, deben definirse esos procesos y las reglas legales/fiscales aplicables.

## Verificación rápida

1. Importa la migración y promueve un administrador.
2. Registra una cuenta cliente y confirma que no pueda abrir `admin/`.
3. Crea categoría y producto desde administración, luego regístrale stock mediante una compra.
4. Entra como cliente, realiza un pedido y verifica el movimiento `venta`.
5. Cancela el pedido desde administración y comprueba que el saldo se reponga una sola vez.
