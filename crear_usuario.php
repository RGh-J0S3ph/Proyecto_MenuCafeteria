<?php

session_start();

require_once "config/database.php";

$error = "";
$exito = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = $_POST["nombre"] ?? "";
    $email = $_POST["email"] ?? "";
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
</head>

<body>

    <h1>
        Crear usuario
    </h1>

    <?php if ($error !== ""): ?>

        <p>
            <?php echo $error; ?>
        </p>

    <?php endif; ?>


    <?php if ($exito !== ""): ?>

        <p>
            <?php echo $exito; ?>
        </p>

    <?php endif; ?>


    <form action="crear_usuario.php" method="POST">

        <label for="nombre">
            Nombre:
        </label>

        <input
            type="text"
            id="nombre"
            name="nombre"
            required
        >

        <br><br>


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
            Crear usuario
        </button>

    </form>

    <br>

    <a href="login.php">
        <button>
        Regresar al login
    </button> 
    </a>

</body>

</html>