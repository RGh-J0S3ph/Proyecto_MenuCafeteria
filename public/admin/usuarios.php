<?php

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/config/database.php';
requerir_admin();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $usuarioId = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
    if ($usuarioId && $usuarioId !== (int) $_SESSION['usuario_id']) {
        $stmt = $conexion->prepare(
            "UPDATE usuarios SET activo = IF(activo = 1, 0, 1) WHERE id = :id AND rol = 'cliente'"
        );
        $stmt->execute(['id' => $usuarioId]);
        $mensaje = $stmt->rowCount() ? 'Estado de la cuenta actualizado.' : 'No se modificó la cuenta.';
    } else {
        $mensaje = 'No puedes modificar esa cuenta.';
    }
}

$usuarios = $conexion->query(
    "SELECT id, nombre, email, activo FROM usuarios WHERE rol = 'cliente' ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cuentas de clientes | Administración</title><link rel="stylesheet" href="../assets/css/styles.css"></head>
<body><main class="dashboard"><div class="dashboard-topbar"><div class="dashboard-brand">MENÚ <strong>CAFETERÍA</strong> / CLIENTES</div><a class="btn-ghost" href="index.php">Administración</a></div>
<section class="dashboard-main store-main"><p class="dashboard-eyebrow">CUENTAS</p><h1>Clientes registrados</h1><?php if ($mensaje !== ''): ?><div class="alert alert-success"><?php echo escapar($mensaje); ?></div><?php endif; ?>
<section class="store-section"><div class="table-wrap"><table><thead><tr><th>Cliente</th><th>Correo</th><th>Estado</th><th>Acción</th></tr></thead><tbody><?php foreach ($usuarios as $usuario): ?><tr><td><?php echo escapar($usuario['nombre']); ?></td><td><?php echo escapar($usuario['email']); ?></td><td><?php echo $usuario['activo'] ? 'Activo' : 'Desactivado'; ?></td><td><form method="POST"><input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>"><input type="hidden" name="usuario_id" value="<?php echo (int) $usuario['id']; ?>"><button class="btn-ghost" type="submit"><?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?></button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
</section></main></body></html>
