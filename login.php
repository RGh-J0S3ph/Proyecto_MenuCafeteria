<?php

session_start();

require_once "config/database.php";

$email = $_POST["email"] ?? "";
$password = $_POST["password"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $sql = "SELECT * FROM usuarios WHERE email = :email";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "email" => $email
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($password, $usuario["password"])) {

        $_SESSION["usuario_id"] = $usuario["id"];
        $_SESSION["usuario_nombre"] = $usuario["nombre"];
        $_SESSION["usuario_email"] = $usuario["email"];

        header("Location: dashboard.php");
        exit;

    } else {

        echo "<script>
            alert('Correo o contraseña incorrectos');
        </script>";
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio de Sesión</title>
</head>

<body>
    <h1>Iniciar sesión</h1>
    <form id="login "action="login.php" method="POST">
        <label for="email">
            Correo:
        </label>

        <input
            type="email"
            id="email"
            name="email"
            required
        >
        <br><br>
        <label for="password">
            Contraseña:
        </label>
        
        <input
            type="password"
            id="password"
            name="password"
            required
        >

        <br><br>

        <button type="submit">
            Iniciar Sesión
        </button>

        

    </form>

<br>

<a href="crear_usuario.php">
    <button type="button">
        Crear usuario
    </button>
</a>

</body>
</html>

<?php if (isset($_GET["error"])): ?>

    <script>
        alert("Correo o contraseña incorrectos");
    </script>

<?php endif; ?>


    