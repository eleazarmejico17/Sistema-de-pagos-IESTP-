<?php
session_start();

class AuthController {

    public function logout() {
        session_unset();
        session_destroy();

        // Redirige al login en public
        header("Location: /Sistema-de-pagos-IESTP/index.html");
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] === "logout") {
    $auth = new AuthController();
    $auth->logout();
}
