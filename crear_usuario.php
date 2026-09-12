<?php

session_start();

require_once "config/database.php";

$error = "";
$exito = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($nombre === "" || $email === "" || $password === "") {

        $error = "Todos los campos son obligatorios";

    } else {

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nombre, email, password)
                VALUES (:nombre, :email, :password)";

        try {

            $stmt = $conexion->prepare($sql);

            $stmt->execute([
                "nombre" => $nombre,
                "email" => $email,
                "password" => $passwordHash
            ]);

            $exito = "Usuario creado correctamente";

        } catch (PDOException $e) {

            $error = "No se pudo crear el usuario";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <title>
        Crear usuario
    </title>

    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="auth-shell">
        <div class="login-container">

            <p class="auth-eyebrow">MENÚ CAFETERÍA</p>
            <h1>Crear usuario</h1>
            <p class="auth-subtext">Regístrate para acceder al panel</p>

            <?php if ($error !== ""): ?>
                <div class="alert alert-error">
                    <span class="alert-marker">!</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($exito !== ""): ?>
                <div class="alert alert-success">
                    <span class="alert-marker">✓</span>
                    <span><?php echo htmlspecialchars($exito); ?></span>
                </div>
            <?php endif; ?>

            <form action="crear_usuario.php" method="POST">

                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        required
                    >
                </div>

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
                    Crear usuario
                </button>

            </form>

            <div class="auth-footer">
                <a href="login.php">Regresar al login</a>
            </div>

        </div>
    </div>
</body>

</html>
