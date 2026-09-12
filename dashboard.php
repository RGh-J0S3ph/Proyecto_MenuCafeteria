<?php

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    session_destroy();

    header("Location: login.php");
    exit;
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>
        Dashboard
    </title>

    <link rel="stylesheet" href="styles.css">

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
