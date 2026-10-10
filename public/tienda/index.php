<?php

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/config/database.php';
requerir_cliente();

$mensaje = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$_SESSION['carrito'] = $_SESSION['carrito'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'agregar') {
        $productoId = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
        $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);
        if ($productoId && $cantidad && $cantidad > 0) {
            $stmt = $conexion->prepare('SELECT id FROM productos WHERE id = :id AND activo = 1');
            $stmt->execute(['id' => $productoId]);
            if ($stmt->fetchColumn()) {
                $_SESSION['carrito'][$productoId] = ($_SESSION['carrito'][$productoId] ?? 0) + $cantidad;
                $_SESSION['flash'] = 'Producto agregado al carrito.';
            }
        }
        header('Location: index.php#catalogo');
        exit;
    }

    if ($accion === 'quitar') {
        $productoId = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
        if ($productoId) {
            unset($_SESSION['carrito'][$productoId]);
        }
        header('Location: index.php#carrito');
        exit;
    }

    if ($accion === 'comprar') {
        $carrito = $_SESSION['carrito'];
        if (!$carrito) {
            $_SESSION['flash'] = 'Tu carrito está vacío.';
            header('Location: index.php#carrito');
            exit;
        }

        try {
            $conexion->beginTransaction();
            $productosPedido = [];
            $totalCentavos = 0;
            $consulta = $conexion->prepare(
                'SELECT id, nombre, precio, stock FROM productos WHERE id = :id AND activo = 1 FOR UPDATE'
            );

            foreach ($carrito as $productoId => $cantidad) {
                $cantidad = filter_var($cantidad, FILTER_VALIDATE_INT);
                if (!$cantidad || $cantidad < 1) {
                    throw new RuntimeException('Cantidad de producto inválida.');
                }
                $consulta->execute(['id' => (int) $productoId]);
                $producto = $consulta->fetch(PDO::FETCH_ASSOC);
                if (!$producto || (float) $producto['stock'] < $cantidad) {
                    throw new RuntimeException('No hay existencias suficientes para completar el pedido.');
                }
                $productosPedido[] = [$producto, $cantidad];
                $totalCentavos += (int) round((float) $producto['precio'] * 100) * $cantidad;
            }

            $total = number_format($totalCentavos / 100, 2, '.', '');
            $insertarPedido = $conexion->prepare(
                "INSERT INTO pedidos (usuario_id, estado, total) VALUES (:usuario_id, 'pendiente', :total)"
            );
            $insertarPedido->execute([
                'usuario_id' => $_SESSION['usuario_id'],
                'total' => $total
            ]);
            $pedidoId = (int) $conexion->lastInsertId();

            $insertarDetalle = $conexion->prepare(
                'INSERT INTO detalle_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario) '
                . 'VALUES (:pedido_id, :producto_id, :nombre, :cantidad, :precio)'
            );
            $descontarStock = $conexion->prepare(
                'UPDATE productos SET stock = stock - :cantidad WHERE id = :id AND stock >= :cantidad'
            );
            $insertarMovimiento = $conexion->prepare(
                "INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, referencia) "
                . "VALUES (:producto_id, :usuario_id, 'venta', :cantidad, :referencia)"
            );

            foreach ($productosPedido as [$producto, $cantidad]) {
                $insertarDetalle->execute([
                    'pedido_id' => $pedidoId,
                    'producto_id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'cantidad' => $cantidad,
                    'precio' => $producto['precio']
                ]);
                $descontarStock->execute(['cantidad' => $cantidad, 'id' => $producto['id']]);
                if ($descontarStock->rowCount() !== 1) {
                    throw new RuntimeException('El inventario cambió. Vuelve a intentar el pedido.');
                }
                $insertarMovimiento->execute([
                    'producto_id' => $producto['id'],
                    'usuario_id' => $_SESSION['usuario_id'],
                    'cantidad' => -$cantidad,
                    'referencia' => 'Pedido #' . $pedidoId
                ]);
            }

            $conexion->commit();
            $_SESSION['carrito'] = [];
            $_SESSION['flash'] = 'Pedido #' . $pedidoId . ' registrado correctamente.';
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $_SESSION['flash'] = $error instanceof RuntimeException
                ? $error->getMessage()
                : 'No se pudo registrar el pedido. Inténtalo nuevamente.';
        }

        header('Location: index.php#pedidos');
        exit;
    }
}

$tipoMenu = $_GET['tipo'] ?? '';
if (!in_array($tipoMenu, ['', 'bebida', 'refrigerio'], true)) {
    $tipoMenu = '';
}
$busquedaMenu = isset($_GET['q']) && is_string($_GET['q']) ? trim(substr($_GET['q'], 0, 100)) : '';
$consultaMenu =
    'SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, p.tipo, c.nombre AS categoria '
    . 'FROM productos p LEFT JOIN categorias c ON c.id = p.categoria_id WHERE p.activo = 1';
$parametrosMenu = [];
if ($tipoMenu !== '') {
    $consultaMenu .= ' AND p.tipo = :tipo';
    $parametrosMenu['tipo'] = $tipoMenu;
}
if ($busquedaMenu !== '') {
    $consultaMenu .= ' AND (p.nombre LIKE :nombre OR p.descripcion LIKE :descripcion)';
    $parametrosMenu['nombre'] = '%' . $busquedaMenu . '%';
    $parametrosMenu['descripcion'] = '%' . $busquedaMenu . '%';
}
$consultaMenu .= ' ORDER BY c.nombre, p.nombre';
$stmt = $conexion->prepare($consultaMenu);
$stmt->execute($parametrosMenu);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$productosCarrito = [];
$totalCarritoCentavos = 0;
if ($_SESSION['carrito']) {
    $ids = array_map('intval', array_keys($_SESSION['carrito']));
    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conexion->prepare("SELECT id, nombre, precio FROM productos WHERE activo = 1 AND id IN ($marcadores)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $producto) {
        $cantidad = (int) ($_SESSION['carrito'][$producto['id']] ?? 0);
        $producto['cantidad'] = $cantidad;
        $producto['subtotal'] = (int) round((float) $producto['precio'] * 100) * $cantidad;
        $totalCarritoCentavos += $producto['subtotal'];
        $productosCarrito[] = $producto;
    }
}

$stmt = $conexion->prepare(
    'SELECT id, estado, total, creado_en FROM pedidos WHERE usuario_id = :usuario_id ORDER BY creado_en DESC LIMIT 20'
);
$stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tienda | Menú Cafetería</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <main class="dashboard">
        <div class="dashboard-topbar">
            <div class="dashboard-brand">MENÚ <strong>CAFETERÍA</strong></div>
            <div class="store-user">
                <span><?php echo escapar($_SESSION['usuario_nombre'] ?? ''); ?></span>
                <form action="../logout.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>">
                    <button type="submit" class="btn-ghost">Cerrar sesión</button>
                </form>
            </div>
        </div>
        <section class="dashboard-main store-main">
            <div class="dashboard-welcome">
                <p class="dashboard-eyebrow">TIENDA</p>
                    <h1>Menú de bebidas y refrigerios</h1>
                <p>Hola, <?php echo escapar($_SESSION['usuario_nombre'] ?? ''); ?>.</p>
            </div>

            <?php if ($mensaje !== ''): ?>
                <div class="alert alert-success"><?php echo escapar($mensaje); ?></div>
            <?php endif; ?>

            <section id="catalogo" class="store-section">
                <h2>Menú</h2>
                <form method="GET" action="index.php#catalogo" class="menu-filters">
                    <label for="busqueda-menu">Buscar producto
                        <input id="busqueda-menu" type="search" name="q" maxlength="100" value="<?php echo escapar($busquedaMenu); ?>" placeholder="Nombre o descripción">
                    </label>
                    <label for="tipo-menu">Tipo
                        <select id="tipo-menu" name="tipo">
                            <option value="">Todos</option>
                            <option value="bebida" <?php echo $tipoMenu === 'bebida' ? 'selected' : ''; ?>>Bebidas</option>
                            <option value="refrigerio" <?php echo $tipoMenu === 'refrigerio' ? 'selected' : ''; ?>>Refrigerios</option>
                        </select>
                    </label>
                    <button type="submit" class="btn-primary">Buscar</button>
                    <?php if ($tipoMenu !== '' || $busquedaMenu !== ''): ?><a class="btn-ghost" href="index.php#catalogo">Limpiar</a><?php endif; ?>
                </form>
                <?php if (!$productos): ?>
                    <p><?php echo $tipoMenu !== '' || $busquedaMenu !== '' ? 'No hay productos que coincidan con la búsqueda.' : 'Aún no hay productos disponibles.'; ?></p>
                <?php else: ?>
                    <div class="store-grid">
                        <?php foreach ($productos as $producto): ?>
                            <article class="store-item">
                                <p class="dashboard-eyebrow"><?php echo $producto['tipo'] === 'bebida' ? 'BEBIDA' : 'REFRIGERIO'; ?> · <?php echo escapar($producto['categoria'] ?? 'CAFETERÍA'); ?></p>
                                <h3><?php echo escapar($producto['nombre']); ?></h3>
                                <p><?php echo escapar($producto['descripcion'] ?? ''); ?></p>
                                <p class="store-price">$<?php echo number_format((float) $producto['precio'], 2); ?></p>
                                <p class="store-stock">Disponibles: <?php echo escapar($producto['stock']); ?></p>
                                <?php if ((float) $producto['stock'] > 0): ?>
                                    <form method="POST" class="store-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>">
                                        <input type="hidden" name="accion" value="agregar">
                                        <input type="hidden" name="producto_id" value="<?php echo (int) $producto['id']; ?>">
                                        <label for="cantidad-<?php echo (int) $producto['id']; ?>">Cantidad</label>
                                        <input id="cantidad-<?php echo (int) $producto['id']; ?>" type="number" name="cantidad" min="1" max="<?php echo (int) $producto['stock']; ?>" value="1" required>
                                        <button type="submit" class="btn-primary">Agregar</button>
                                    </form>
                                <?php else: ?>
                                    <p>Agotado</p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section id="carrito" class="store-section">
                <h2>Tu carrito</h2>
                <?php if (!$productosCarrito): ?>
                    <p>No tienes productos en el carrito.</p>
                <?php else: ?>
                    <div class="table-wrap"><table>
                        <thead><tr><th>Producto</th><th>Cantidad</th><th>Subtotal</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($productosCarrito as $item): ?>
                            <tr>
                                <td><?php echo escapar($item['nombre']); ?></td>
                                <td><?php echo (int) $item['cantidad']; ?></td>
                                <td>$<?php echo number_format($item['subtotal'] / 100, 2); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>">
                                        <input type="hidden" name="accion" value="quitar">
                                        <input type="hidden" name="producto_id" value="<?php echo (int) $item['id']; ?>">
                                        <button type="submit" class="btn-ghost">Quitar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                    <p class="store-total">Total: $<?php echo number_format($totalCarritoCentavos / 100, 2); ?></p>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>">
                        <input type="hidden" name="accion" value="comprar">
                        <button type="submit" class="btn-primary">Confirmar pedido</button>
                    </form>
                <?php endif; ?>
            </section>

            <section id="pedidos" class="store-section">
                <h2>Mis pedidos</h2>
                <?php if (!$pedidos): ?>
                    <p>Tus pedidos aparecerán aquí.</p>
                <?php else: ?>
                    <div class="table-wrap"><table>
                        <thead><tr><th>Pedido</th><th>Fecha</th><th>Estado</th><th>Total</th></tr></thead>
                        <tbody><?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>#<?php echo (int) $pedido['id']; ?></td>
                                <td><?php echo escapar($pedido['creado_en']); ?></td>
                                <td><?php echo escapar(ucfirst($pedido['estado'])); ?></td>
                                <td>$<?php echo number_format((float) $pedido['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
            </section>
        </section>
    </main>
</body>
</html>
