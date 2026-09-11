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

</head>

<body>

    <header>
        Bienvenido
    </header>

    <img src="assets/img/kirktriplet.jpg" alt="imagen de bienvenida" class="img-fluid rounded shadow-sm">

    <form action="dashboard.php" method="POST">

        <button type="submit">
            Cerrar sesión
        </button>

    </form>

</body>

</html>