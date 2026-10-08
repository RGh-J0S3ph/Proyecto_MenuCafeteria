<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requerir_admin();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $productoId = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
    $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_FLOAT);
    $motivo = trim($_POST['motivo'] ?? '');
    try {
        if (!$productoId || $cantidad === false || $cantidad == 0 || $motivo === '') {
            throw new RuntimeException('Selecciona un producto, una cantidad distinta de cero y explica el motivo.');
        }
        $conexion->beginTransaction();
        $stmt = $conexion->prepare('SELECT stock FROM productos WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $productoId]);
        $stockActual = $stmt->fetchColumn();
        if ($stockActual === false) {
            throw new RuntimeException('El producto no existe.');
        }
        if ((float) $stockActual + $cantidad < 0) {
            throw new RuntimeException('El ajuste dejaría el inventario en negativo.');
        }
        $stmt = $conexion->prepare('UPDATE productos SET stock = stock + :cantidad WHERE id = :id');
        $stmt->execute(['cantidad' => $cantidad, 'id' => $productoId]);
        $stmt = $conexion->prepare("INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, referencia) VALUES (:producto, :usuario, 'ajuste', :cantidad, :referencia)");
        $stmt->execute([
            'producto' => $productoId,
            'usuario' => $_SESSION['usuario_id'],
            'cantidad' => number_format($cantidad, 2, '.', ''),
            'referencia' => mb_substr($motivo, 0, 150)
        ]);
        $conexion->commit();
        $mensaje = 'Ajuste aplicado.';
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        $mensaje = $error instanceof RuntimeException ? $error->getMessage() : 'No se pudo aplicar el ajuste.';
    }
}

$productos = $conexion->query('SELECT id, nombre, stock FROM productos ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
$movimientos = $conexion->query(
    'SELECT m.creado_en, p.nombre AS producto, u.nombre AS usuario, m.tipo, m.cantidad, m.referencia '
    . 'FROM movimientos_inventario m JOIN productos p ON p.id = m.producto_id '
    . 'JOIN usuarios u ON u.id = m.usuario_id ORDER BY m.creado_en DESC LIMIT 100'
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Inventario | Administración</title><link rel="stylesheet" href="../styles.css"></head>
<body><main class="dashboard"><div class="dashboard-topbar"><div class="dashboard-brand">MENÚ <strong>CAFETERÍA</strong> / INVENTARIO</div><a class="btn-ghost" href="index.php">Administración</a></div>
<section class="dashboard-main store-main"><p class="dashboard-eyebrow">EXISTENCIAS</p><h1>Ajustes de inventario</h1><?php if ($mensaje !== ''): ?><div class="alert alert-success"><?php echo escapar($mensaje); ?></div><?php endif; ?>
<section class="store-section"><h2>Registrar ajuste manual</h2><form method="POST" class="admin-form admin-form-grid"><input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>"><label>Producto<select name="producto_id" required><?php foreach ($productos as $producto): ?><option value="<?php echo (int) $producto['id']; ?>"><?php echo escapar($producto['nombre']); ?> (stock: <?php echo escapar($producto['stock']); ?>)</option><?php endforeach; ?></select></label><label>Cantidad (+ entrada, - salida)<input type="number" name="cantidad" step="0.01" required></label><label class="admin-wide">Motivo<input name="motivo" maxlength="150" required></label><button class="btn-primary" type="submit">Aplicar ajuste</button></form></section>
<section class="store-section"><h2>Movimientos recientes</h2><div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad (+/-)</th><th>Referencia</th><th>Usuario</th></tr></thead><tbody><?php foreach ($movimientos as $movimiento): ?><tr><td><?php echo escapar($movimiento['creado_en']); ?></td><td><?php echo escapar($movimiento['producto']); ?></td><td><?php echo escapar($movimiento['tipo']); ?></td><td><?php echo escapar($movimiento['cantidad']); ?></td><td><?php echo escapar($movimiento['referencia']); ?></td><td><?php echo escapar($movimiento['usuario']); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</section></main></body></html>
