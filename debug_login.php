<?php
// Script temporal de diagnóstico. BORRAR después de usarlo.
require_once "config/database.php";

echo "<pre>";

echo "== Estructura de la tabla usuarios ==\n";
try {
    $desc = $conexion->query("DESCRIBE usuarios");
    foreach ($desc->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo str_pad($col["Field"], 15) . " " . $col["Type"] . "\n";
    }
} catch (PDOException $e) {
    echo "ERROR al describir tabla: " . $e->getMessage() . "\n";
}

echo "\n== Usuarios registrados ==\n";
try {
    $stmt = $conexion->query("SELECT id, nombre, email, password, LENGTH(password) AS len_password FROM usuarios");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) === 0) {
        echo "No hay ningún usuario en la tabla. Debes crear uno primero en crear_usuario.php\n";
    }
    foreach ($rows as $r) {
        echo "id=" . $r["id"]
            . " email='" . $r["email"] . "'"
            . " nombre='" . $r["nombre"] . "'"
            . " len_password=" . $r["len_password"]
            . " password_empieza_con='" . substr($r["password"], 0, 10) . "...'"
            . "\n";
    }
} catch (PDOException $e) {
    echo "ERROR al leer usuarios: " . $e->getMessage() . "\n";
}

echo "\nNota: un hash de password_hash() normal empieza con \$2y\$ y mide 60 caracteres.\n";
echo "Si len_password es menor a 60, la columna password está truncando el hash y el login SIEMPRE fallará.\n";
echo "</pre>";
