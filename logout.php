<?php
/**
 * Shoele Store — Logout
 */
require_once __DIR__ . '/includes/functions.php';

// Destruir a sessão
$_SESSION = [];
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();

// Redirecionar para a página de entrada
header('Location: /entrada.php');
exit;
