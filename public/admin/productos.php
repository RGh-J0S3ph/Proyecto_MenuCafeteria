<?php

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/config/database.php';
requerir_admin();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'categoria') {
            $nombre = trim($_POST['nombre_categoria'] ?? '');
            if ($nombre === '') {
                throw new RuntimeException('Escribe el nombre de la categoría.');
            }
            $stmt = $conexion->prepare('INSERT INTO categorias (nombre) VALUES (:nombre)');
            $stmt->execute(['nombre' => $nombre]);
            $mensaje = 'Categoría creada.';
        } elseif ($accion === 'producto') {
            $id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT) ?: null;
            $categoriaId = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT) ?: null;
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT);
            $activo = isset($_POST['activo']) ? 1 : 0;
            $stockInicial = filter_var($_POST['stock_inicial'] ?? '0', FILTER_VALIDATE_FLOAT);

            if ($nombre === '' || $precio === false || $precio <= 0 || (!$id && ($stockInicial === false || $stockInicial < 0))) {
                throw new RuntimeException('Revisa el nombre, precio y stock inicial.');
            }

            if ($id) {
                $stmt = $conexion->prepare(
                    'UPDATE productos SET categoria_id = :categoria_id, nombre = :nombre, '
                    . 'descripcion = :descripcion, precio = :precio, activo = :activo WHERE id = :id'
                );
                $stmt->execute([
                    'categoria_id' => $categoriaId,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'precio' => number_format($precio, 2, '.', ''),
                    'activo' => $activo,
                    'id' => $id
                ]);
                $mensaje = 'Producto actualizado.';
            } else {
                $conexion->beginTransaction();
                $stmt = $conexion->prepare(
                    'INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, activo) '
                    . 'VALUES (:categoria_id, :nombre, :descripcion, :precio, :stock, 1)'
                );
                $stmt->execute([
                    'categoria_id' => $categoriaId,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'precio' => number_format($precio, 2, '.', ''),
                    'stock' => number_format($stockInicial, 2, '.', '')
                ]);
                $productoId = (int) $conexion->lastInsertId();
                if ($stockInicial > 0) {
                    $stmt = $conexion->prepare(
                        "INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, referencia) "
                        . "VALUES (:producto_id, :usuario_id, 'ajuste', :cantidad, 'Stock inicial')"
                    );
                    $stmt->execute([
                        'producto_id' => $productoId,
                        'usuario_id' => $_SESSION['usuario_id'],
                        'cantidad' => number_format($stockInicial, 2, '.', '')
                    ]);
                }
                $conexion->commit();
                $mensaje = 'Producto creado.';
            }
        } elseif ($accion === 'estado') {
            $id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
            $stmt = $conexion->prepare('UPDATE productos SET activo = IF(activo = 1, 0, 1) WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $mensaje = 'Estado del producto actualizado.';
        }
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        $mensaje = $error instanceof RuntimeException ? $error->getMessage() : 'No se pudo guardar. Verifica que la categoría no esté repetida.';
    }
}

$categorias = $conexion->query('SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
$productos = $conexion->query(
    'SELECT p.*, c.nombre AS categoria FROM productos p LEFT JOIN categorias c ON c.id = p.categoria_id ORDER BY p.nombre'
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Productos | Administración</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<main class="dashboard">
    <div class="dashboard-topbar"><div class="dashboard-brand">MENÚ <strong>CAFETERÍA</strong> / PRODUCTOS</div><a class="btn-ghost" href="index.php">Administración</a></div>
    <section class="dashboard-main store-main">
        <p class="dashboard-eyebrow">CATÁLOGO</p><h1>Productos y categorías</h1>
        <?php if ($mensaje !== ''): ?><div class="alert alert-success"><?php echo escapar($mensaje); ?></div><?php endif; ?>
        <section class="store-section">
            <h2>Nueva categoría</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>"><input type="hidden" name="accion" value="categoria">
                <label for="nombre_categoria">Nombre</label><input id="nombre_categoria" name="nombre_categoria" maxlength="100" required>
                <button class="btn-primary" type="submit">Crear categoría</button>
            </form>
        </section>
        <section class="store-section">
            <h2>Nuevo producto</h2>
            <form method="POST" class="admin-form admin-form-grid">
                <input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>"><input type="hidden" name="accion" value="producto">
                <label>Nombre<input name="nombre" maxlength="150" required></label>
                <label>Categoría<select name="categoria_id"><option value="">Sin categoría</option><?php foreach ($categorias as $categoria): ?><option value="<?php echo (int) $categoria['id']; ?>"><?php echo escapar($categoria['nombre']); ?></option><?php endforeach; ?></select></label>
                <label>Precio<input type="number" name="precio" min="0.01" step="0.01" required></label>
                <label>Existencia inicial<input type="number" name="stock_inicial" min="0" step="0.01" value="0" required></label>
                <label class="admin-wide">Descripción<textarea name="descripcion" rows="2"></textarea></label>
                <button class="btn-primary" type="submit">Crear producto</button>
            </form>
        </section>
        <section class="store-section"><h2>Inventario de productos</h2>
            <div class="table-wrap"><table><thead><tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Editar</th></tr></thead><tbody>
            <?php foreach ($productos as $producto): ?>
                <tr>
                    <td><?php echo escapar($producto['nombre']); ?></td><td><?php echo escapar($producto['categoria'] ?? 'Sin categoría'); ?></td>
                    <td>$<?php echo number_format((float) $producto['precio'], 2); ?></td><td><?php echo escapar($producto['stock']); ?></td><td><?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?></td>
                    <td>
                        <details><summary>Editar</summary>
                            <form method="POST" class="admin-form">
                                <input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>"><input type="hidden" name="accion" value="producto"><input type="hidden" name="producto_id" value="<?php echo (int) $producto['id']; ?>">
                                <label>Nombre<input name="nombre" value="<?php echo escapar($producto['nombre']); ?>" required></label>
                                <label>Categoría<select name="categoria_id"><option value="">Sin categoría</option><?php foreach ($categorias as $categoria): ?><option value="<?php echo (int) $categoria['id']; ?>" <?php echo (int) $producto['categoria_id'] === (int) $categoria['id'] ? 'selected' : ''; ?>><?php echo escapar($categoria['nombre']); ?></option><?php endforeach; ?></select></label>
                                <label>Precio<input type="number" name="precio" min="0.01" step="0.01" value="<?php echo escapar($producto['precio']); ?>" required></label>
                                <label>Descripción<textarea name="descripcion" rows="2"><?php echo escapar($producto['descripcion'] ?? ''); ?></textarea></label>
                                <label><input type="checkbox" name="activo" value="1" <?php echo $producto['activo'] ? 'checked' : ''; ?>> Activo</label>
                                <button class="btn-primary" type="submit">Guardar cambios</button>
                            </form>
                        </details>
                        <form method="POST" class="inline-form"><input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="producto_id" value="<?php echo (int) $producto['id']; ?>"><button class="btn-ghost" type="submit"><?php echo $producto['activo'] ? 'Desactivar' : 'Activar'; ?></button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody></table></div>
        </section>
    </section>
</main>
</body>
</html>
