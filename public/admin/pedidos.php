<?php

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/config/database.php';
requerir_admin();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $pedidoId = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
    $accion = $_POST['accion'] ?? 'cancelar';
    try {
        if (!$pedidoId) {
            throw new RuntimeException('Pedido inválido.');
        }
        $conexion->beginTransaction();
        $stmt = $conexion->prepare('SELECT estado FROM pedidos WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $pedidoId]);
        $estado = $stmt->fetchColumn();
        if ($estado !== 'pendiente') {
            throw new RuntimeException('Solo se pueden actualizar pedidos pendientes.');
        }

        if ($accion === 'completar') {
            $nuevoEstado = 'completado';
        } else {
            $stmt = $conexion->prepare('SELECT producto_id, cantidad FROM detalle_pedido WHERE pedido_id = :pedido');
            $stmt->execute(['pedido' => $pedidoId]);
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $sumarStock = $conexion->prepare('UPDATE productos SET stock = stock + :cantidad WHERE id = :id');
            $movimiento = $conexion->prepare(
                "INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, referencia) "
                . "VALUES (:producto, :usuario, 'ajuste', :cantidad, :referencia)"
            );
            foreach ($detalles as $detalle) {
                $sumarStock->execute(['cantidad' => $detalle['cantidad'], 'id' => $detalle['producto_id']]);
                $movimiento->execute([
                    'producto' => $detalle['producto_id'],
                    'usuario' => $_SESSION['usuario_id'],
                    'cantidad' => $detalle['cantidad'],
                    'referencia' => 'Cancelación pedido #' . $pedidoId
                ]);
            }
            $nuevoEstado = 'cancelado';
        }
        $stmt = $conexion->prepare('UPDATE pedidos SET estado = :estado WHERE id = :id AND estado = \'pendiente\'');
        $stmt->execute(['estado' => $nuevoEstado, 'id' => $pedidoId]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('No se pudo cambiar el estado del pedido.');
        }
        $conexion->commit();
        $mensaje = $nuevoEstado === 'completado'
            ? 'Pedido marcado como completado.'
            : 'Pedido cancelado y stock restituido.';
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        $mensaje = $error instanceof RuntimeException ? $error->getMessage() : 'No se pudo cancelar el pedido.';
    }
}

$pedidos = $conexion->query(
    'SELECT p.id, p.usuario_id, p.estado, p.total, p.creado_en, u.nombre, u.email '
    . 'FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.creado_en DESC LIMIT 100'
)->fetchAll(PDO::FETCH_ASSOC);
$detallesStmt = $conexion->prepare(
    'SELECT nombre_producto, cantidad, precio_unitario FROM detalle_pedido WHERE pedido_id = :pedido ORDER BY id'
);

?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pedidos | Administración</title><link rel="stylesheet" href="../assets/css/styles.css"></head>
<body><main class="dashboard"><div class="dashboard-topbar"><div class="dashboard-brand">MENÚ <strong>CAFETERÍA</strong> / PEDIDOS</div><a class="btn-ghost" href="index.php">Administración</a></div>
<section class="dashboard-main store-main"><p class="dashboard-eyebrow">VENTAS</p><h1>Pedidos de clientes</h1><?php if ($mensaje !== ''): ?><div class="alert alert-success"><?php echo escapar($mensaje); ?></div><?php endif; ?>
<section class="store-section"><div class="table-wrap"><table><thead><tr><th>Pedido</th><th>Cliente</th><th>Detalle</th><th>Total</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php foreach ($pedidos as $pedido): ?>
    <?php $detallesStmt->execute(['pedido' => $pedido['id']]); $detalles = $detallesStmt->fetchAll(PDO::FETCH_ASSOC); ?>
    <tr><td>#<?php echo (int) $pedido['id']; ?></td><td><?php echo escapar($pedido['nombre']); ?><br><?php echo escapar($pedido['email']); ?></td><td><?php foreach ($detalles as $detalle): ?><?php echo escapar($detalle['nombre_producto']); ?> x <?php echo escapar($detalle['cantidad']); ?> ($<?php echo number_format((float) $detalle['precio_unitario'], 2); ?>)<br><?php endforeach; ?></td><td>$<?php echo number_format((float) $pedido['total'], 2); ?></td><td><?php echo escapar($pedido['creado_en']); ?></td><td><?php echo escapar(ucfirst($pedido['estado'])); ?></td><td><?php if ($pedido['estado'] === 'pendiente'): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>"><input type="hidden" name="pedido_id" value="<?php echo (int) $pedido['id']; ?>"><input type="hidden" name="accion" value="completar"><button class="btn-ghost" type="submit">Completar</button></form><form method="POST" onsubmit="return confirm('¿Cancelar el pedido y devolver sus unidades al inventario?')"><input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>"><input type="hidden" name="pedido_id" value="<?php echo (int) $pedido['id']; ?>"><input type="hidden" name="accion" value="cancelar"><button class="btn-ghost" type="submit">Cancelar</button></form><?php else: ?>-<?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></section></section></main></body></html>
