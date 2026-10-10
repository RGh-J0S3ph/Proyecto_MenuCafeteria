<?php

session_start();

require_once __DIR__ . '/../app/config/database.php';

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $sql = "SELECT * FROM usuarios WHERE email = :email AND activo = 1";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "email" => $email
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($password, $usuario["password"])) {

        $_SESSION["usuario_id"] = $usuario["id"];
        $_SESSION["usuario_nombre"] = $usuario["nombre"];
        $_SESSION["usuario_email"] = $usuario["email"];
        $_SESSION["usuario_rol"] = $usuario["rol"] ?? "cliente";

        session_regenerate_id(true);
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <div class="auth-shell">
        <div class="login-container">

            <p class="auth-eyebrow">MENÚ CAFETERÍA</p>
            <h1>Iniciar sesión</h1>
            <p class="auth-subtext">Ingresa tus datos para continuar</p>

            <form id="login" action="login.php" method="POST">

                <div class="form-group">
                    <label for="email">Correo</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                    >
                </div>

                <button type="submit" class="btn-primary">
                    Iniciar Sesión
                </button>

            </form>

            <div class="auth-footer">
                ¿No tienes cuenta? <a href="crear_usuario.php">Crear usuario</a>
            </div>

        </div>
    </div>
</body>
</html>

<?php if (isset($_GET["error"])): ?>

    <script>
        alert("Correo o contraseña incorrectos");
    </script>

<?php endif; ?>


    