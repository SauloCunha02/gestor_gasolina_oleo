<?php
session_start();
session_destroy();

if (isset($_COOKIE['usuario_logado'])) {
    setcookie('usuario_logado', '', time() - 3600, '/');
}

header('Location: login.php');
exit();
?>
