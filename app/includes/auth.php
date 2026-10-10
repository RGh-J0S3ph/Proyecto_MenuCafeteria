<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validar_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('La sesión del formulario expiró. Regresa e inténtalo de nuevo.');
    }
}

function requerir_login(string $rutaLogin = 'login.php'): void
{
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ' . $rutaLogin);
        exit;
    }
}

function requerir_admin(string $rutaLogin = '../login.php'): void
{
    requerir_login($rutaLogin);

    if (($_SESSION['usuario_rol'] ?? 'cliente') !== 'admin') {
        http_response_code(403);
        exit('No tienes permiso para acceder a esta sección.');
    }
}

function requerir_cliente(string $rutaLogin = '../login.php'): void
{
    requerir_login($rutaLogin);

    if (($_SESSION['usuario_rol'] ?? 'cliente') === 'admin') {
        header('Location: ../admin/index.php');
        exit;
    }
}
