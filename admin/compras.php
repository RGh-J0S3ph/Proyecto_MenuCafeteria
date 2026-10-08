<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requerir_admin();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    try {
        if (($_POST['accion'] ?? '') === 'proveedor') {
            $nombre = trim($_POST['nombre'] ?? '');
            $contacto = trim($_POST['contacto'] ?? '');
            if ($nombre === '') {
                throw new RuntimeException('El nombre del proveedor es obligatorio.');
            }
            $stmt = $conexion->prepare('INSERT INTO proveedores (nombre, contacto) VALUES (:nombre, :contacto)');
            $stmt->execute(['nombre' => $nombre, 'contacto' => $contacto !== '' ? $contacto : null]);
            $mensaje = 'Proveedor registrado.';
        } elseif (($_POST['accion'] ?? '') === 'compra') {
            $proveedorId = filter_input(INPUT_POST, 'proveedor_id', FILTER_VALIDATE_INT);
            $productoId = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
            $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_FLOAT);
            $costo = filter_var($_POST['costo_unitario'] ?? null, FILTER_VALIDATE_FLOAT);
            if (!$proveedorId || !$productoId || $cantidad === false || $cantidad <= 0 || $costo === false || $costo < 0) {
                throw new RuntimeException('Revisa proveedor, producto, cantidad y costo.');
            }

            $conexion->beginTransaction();
            $stmt = $conexion->prepare('SELECT id FROM productos WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $productoId]);
            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('El producto ya no existe.');
            }
            $total = number_format($cantidad * $costo, 2, '.', '');
            $stmt = $conexion->prepare('INSERT INTO compras (proveedor_id, usuario_id, total) VALUES (:proveedor, :usuario, :total)');
            $stmt->execute(['proveedor' => $proveedorId, 'usuario' => $_SESSION['usuario_id'], 'total' => $total]);
            $compraId = (int) $conexion->lastInsertId();
            $stmt = $conexion->prepare('INSERT INTO detalle_compra (compra_id, producto_id, cantidad, costo_unitario) VALUES (:compra, :producto, :cantidad, :costo)');
            $stmt->execute(['compra' => $compraId, 'producto' => $productoId, 'cantidad' => $cantidad, 'costo' => number_format($costo, 2, '.', '')]);
            $stmt = $conexion->prepare('UPDATE productos SET stock = stock + :cantidad WHERE id = :id');
            $stmt->execute(['cantidad' => $cantidad, 'id' => $productoId]);
            $stmt = $conexion->prepare("INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, referencia) VALUES (:producto, :usuario, 'compra', :cantidad, :referencia)");
            $stmt->execute(['producto' => $productoId, 'usuario' => $_SESSION['usuario_id'], 'cantidad' => $cantidad, 'referencia' => 'Compra #' . $compraId]);
            $conexion->commit();
            $mensaje = 'Compra recibida. Se actualizó el inventario.';
        }
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        $mensaje = $error instanceof RuntimeException ? $error->getMessage() : 'No se pudo registrar. Verifica la información e inténtalo otra vez.';
    }
}

$proveedores = $conexion->query('SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
$productos = $conexion->query('SELECT id, nombre, stock FROM productos ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
$compras = $conexion->query(
    'SELECT c.id, c.total, c.creado_en, p.nombre AS proveedor, u.nombre AS usuario, '
    . 'GROUP_CONCAT(CONCAT(pr.nombre, " × ", dc.cantidad) SEPARATOR ", ") AS articulos '
    . 'FROM compras c JOIN proveedores p ON p.id = c.proveedor_id JOIN usuarios u ON u.id = c.usuario_id '
    . 'JOIN detalle_compra dc ON dc.compra_id = c.id JOIN productos pr ON pr.id = dc.producto_id '
    . 'GROUP BY c.id ORDER BY c.creado_en DESC LIMIT 50'
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Compras | Administración</title><link rel="stylesheet" href="../styles.css"></head>
<body><main class="dashboard"><div class="dashboard-topbar"><div class="dashboard-brand">MENÚ <strong>CAFETERÍA</strong> / COMPRAS</div><a class="btn-ghost" href="index.php">Administración</a></div>
<section class="dashboard-main store-main"><p class="dashboard-eyebrow">ABASTECIMIENTO</p><h1>Proveedores y compras</h1>
<?php if ($mensaje !== ''): ?><div class="alert alert-success"><?php echo escapar($mensaje); ?></div><?php endif; ?>
<div class="admin-columns"><section class="store-section"><h2>Nuevo proveedor</h2><form method="POST" class="admin-form"><input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>"><input type="hidden" name="accion" value="proveedor"><label>Nombre<input name="nombre" maxlength="150" required></label><label>Contacto<input name="contacto" maxlength="150"></label><button class="btn-primary" type="submit">Guardar proveedor</button></form></section>
<section class="store-section"><h2>Recibir compra</h2><?php if (!$proveedores || !$productos): ?><p>Primero registra al menos un proveedor y un producto.</p><?php else: ?><form method="POST" class="admin-form"><input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>"><input type="hidden" name="accion" value="compra"><label>Proveedor<select name="proveedor_id" required><?php foreach ($proveedores as $proveedor): ?><option value="<?php echo (int) $proveedor['id']; ?>"><?php echo escapar($proveedor['nombre']); ?></option><?php endforeach; ?></select></label><label>Producto<select name="producto_id" required><?php foreach ($productos as $producto): ?><option value="<?php echo (int) $producto['id']; ?>"><?php echo escapar($producto['nombre']); ?> (stock: <?php echo escapar($producto['stock']); ?>)</option><?php endforeach; ?></select></label><label>Cantidad<input type="number" name="cantidad" min="0.01" step="0.01" required></label><label>Costo unitario<input type="number" name="costo_unitario" min="0" step="0.01" required></label><button class="btn-primary" type="submit">Registrar y recibir</button></form><?php endif; ?></section></div>
<section class="store-section"><h2>Compras recientes</h2><div class="table-wrap"><table><thead><tr><th>Compra</th><th>Fecha</th><th>Proveedor</th><th>Artículos</th><th>Total</th><th>Registró</th></tr></thead><tbody><?php foreach ($compras as $compra): ?><tr><td>#<?php echo (int) $compra['id']; ?></td><td><?php echo escapar($compra['creado_en']); ?></td><td><?php echo escapar($compra['proveedor']); ?></td><td><?php echo escapar($compra['articulos']); ?></td><td>$<?php echo number_format((float) $compra['total'], 2); ?></td><td><?php echo escapar($compra['usuario']); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</section></main></body></html>
