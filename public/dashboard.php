<?php

require_once __DIR__ . '/../app/includes/auth.php';
requerir_login();

if (($_SESSION['usuario_rol'] ?? 'cliente') === 'admin') {
    header('Location: admin/index.php');
} else {
    header('Location: tienda/index.php');
}
exit;

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>
        Dashboard
    </title>

    <link rel="stylesheet" href="assets/css/styles.css">

</head>

<body>

    <div class="dashboard">

        <div class="dashboard-topbar">
            <div class="dashboard-brand">MENÚ <strong>CAFETERÍA</strong></div>

            <form action="dashboard.php" method="POST">
                <button type="submit" class="btn-ghost">
                    Cerrar sesión
                </button>
            </form>
        </div>

        <div class="dashboard-main">

            <div class="dashboard-welcome">
                <p class="dashboard-eyebrow">PANEL PRINCIPAL</p>
                <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION["usuario_nombre"]); ?></h1>
                <p>Has iniciado sesión correctamente.</p>
            </div>

            <div class="dashboard-video">
                
            </div>

        </div>

    </div>

</body>

</html>
