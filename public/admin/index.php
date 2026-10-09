<?php

require_once __DIR__ . '/../../app/includes/auth.php';
requerir_admin();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración | Menú Cafetería</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <main class="dashboard">
        <div class="dashboard-topbar">
            <div class="dashboard-brand">MENÚ <strong>CAFETERÍA</strong> / ADMIN</div>
            <form action="../logout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo escapar(csrf_token()); ?>">
                <button type="submit" class="btn-ghost">Cerrar sesión</button>
            </form>
        </div>
        <section class="dashboard-main">
            <div class="dashboard-welcome">
                <p class="dashboard-eyebrow">ADMINISTRACIÓN</p>
                <h1>Hola, <?php echo escapar($_SESSION['usuario_nombre'] ?? ''); ?></h1>
                <p>Gestiona ventas, catálogo y existencias.</p>
            </div>
            <nav class="admin-links">
                <a class="btn-primary" href="productos.php">Productos y categorías</a>
                <a class="btn-primary" href="usuarios.php">Cuentas de clientes</a>
                <a class="btn-primary" href="pedidos.php">Pedidos de clientes</a>
                <a class="btn-primary" href="compras.php">Proveedores y compras</a>
                <a class="btn-primary" href="inventario.php">Ajustes de inventario</a>
                <a class="btn-ghost" href="../tienda/index.php">Ver tienda</a>
            </nav>
        </section>
    </main>
</body>
</html>
