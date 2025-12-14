<?php
session_start();

/* Roles permitidos para la página que lo incluye */
$rolesPermitidos = $rolesPermitidos ?? [];   // se define en cada dashboard

if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])) {
    header('Location: ../public/login.html');   // o login.php
    exit;
}

if (!in_array($_SESSION['rol'], $rolesPermitidos, true)) {
    header('Location: ../errors/403.html');      // página de “No autorizado”
    exit;
}
/* Si llegamos aquí, el usuario está autenticado y autorizado */